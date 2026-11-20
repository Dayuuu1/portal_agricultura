<?php

namespace App\Support\WordPress;

use App\Enums\JobPostingStatus;
use App\Models\ActivityLog;
use App\Models\Agency;
use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\JobPosting;
use App\Models\JobPostingFile;
use App\Models\Link;
use App\Models\OrganizationalUnit;
use App\Models\Post;
use App\Models\Redirect;
use Closure;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Migrates news, downloads, job postings and unit pages from the former WordPress
 * portal (database dump + exported upload folders) into the new portal.
 * Re-running is safe: records are matched by their WordPress ID.
 */
final class WordPressImporter
{
    private const IMAGE_MAX_WIDTH = 1600;

    private const UNIT_PAGES = [
        'direccion-regional' => 'Dirección Regional',
        'oficina-de-control-institucional' => 'Oficina de Control Institucional',
        'planeamiento-y-presupuesto' => 'Oficina de Planeamiento y Presupuesto',
        'unidad-de-politica-agraria' => 'Unidad de Política Agraria',
        'unidad-de-presupuesto' => 'Unidad de Presupuesto',
        'unidad-de-inversiones' => 'Unidad de Inversiones',
        'desarrollo-organizacional-y-de-procesos' => 'Unidad de Desarrollo Organizacional y de Procesos',
        'oficina-de-asesoria-juridica' => 'Oficina de Asesoría Jurídica',
        'oficina-de-administracion' => 'Oficina de Administración',
        'la-unidad-de-contabilidad' => 'Unidad de Contabilidad',
        'unidad-de-tesoreria' => 'Unidad de Tesorería',
        'unidad-de-personal' => 'Unidad de Personal',
        'unidad-de-abastecimiento-y-patrimonio' => 'Unidad de Abastecimiento y Patrimonio',
        'unidad-de-tramite-documentario-y-archivo' => 'Unidad de Trámite Documentario y Archivo',
        'estadistica-agraria-e-informatica' => 'Oficina de Estadística Agraria e Informática',
        'estadistica' => 'Unidad de Estadística',
        'analisis-y-difusion' => 'Unidad de Análisis y Difusión',
        'unidad-informatica-y-sistemas' => 'Unidad de Informática y Sistemas',
        'competitividad-agraria' => 'Dirección de Competitividad Agraria',
        'unidad-de-informacion-agraria' => 'Unidad de Información Agraria',
        'unidad-de-promocion-de-la-competitividad' => 'Unidad de Promoción de la Competitividad',
        'saneamiento-fisico-legal' => 'Dirección de Saneamiento Físico Legal de la Propiedad Rural',
        'unidad-de-predios-agricolas' => 'Unidad de Predios Agrícolas',
        'unidad-de-comunidades-nativas' => 'Unidad de Comunidades Nativas',
        'unidad-de-catastro-agricola' => 'Unidad de Catastro Agrícola',
    ];

    private const PAGE_REDIRECTS = [
        'agencias-agrarias' => '/agencias-agrarias',
        'informacion-agricola' => '/documentos?tipo=estadistica-agricola',
        'informacion-pecuaria' => '/documentos?tipo=estadistica-pecuaria',
        'informacion-agroindustrial' => '/documentos?tipo=informacion-agroindustrial',
        'informacion-complementaria' => '/documentos?tipo=estadistica-complementaria',
        'boletines-informativos' => '/documentos?tipo=boletines-estadisticos',
        'inicio' => '/',
    ];

    /**
     * Logos of the former "enlaces de interés" carousel, by link name. The MINAGRI logo is left
     * out on purpose: it shows the ministry's old name (now MIDAGRI).
     */
    private const LINK_LOGOS = [
        'SENASA' => '2018/07/senasa.jpg',
        'AgroRural' => '2018/07/agrorural.jpg',
        'Agroideas' => '2018/07/agroideas.jpg',
        'Agrobanco' => '2018/07/agrobanco.jpg',
        'INIA' => '2018/07/inia.jpg',
        'ANA' => '2018/07/ana.jpg',
        'RNSDD' => '2018/07/Logo-Consulta-Ciudadana-RNSDD.jpg',
    ];

    /** @var array<int, array{id: int, type: string, status: string, title: string, name: string, date: string, content: string, excerpt: string}> */
    private array $posts = [];

    /** @var array<int, array<string, string>> */
    private array $meta = [];

    /** @var array<int, list<string>> */
    private array $termsByPost = [];

    /** @var array<int, int> */
    private array $downloadCounts = [];

    /** @var array<int, list<array{type: string, id: int, title: string}>> */
    private array $sources = [];

    /** @var array<int, list<int>> Post ID => download IDs it published. */
    private array $containerPosts = [];

    /** @var array<int, string> */
    private array $containerDescriptions = [];

    /** @var array<int, array{url: string, title: string}> */
    private array $downloadTargets = [];

    /** @var array<string, string> */
    private array $storedFiles = [];

    /** @var array<string, string|null> */
    private array $storedImages = [];

    /** @var array<string, int|null> */
    private array $unitIds = [];

    private ImportReport $report;

    private Filesystem $disk;

    private string $documentsRoot;

    public function __construct(
        private readonly MysqlDumpReader $reader,
        string $documentsPath,
        private readonly ?string $imagesPath = null,
        private readonly bool $dryRun = false,
        private readonly ?Closure $progress = null,
        private readonly TextNormalizer $normalizer = new TextNormalizer,
        private readonly SpamDetector $spam = new SpamDetector,
        private readonly DocumentClassifier $classifier = new DocumentClassifier,
        private readonly ContentCleaner $cleaner = new ContentCleaner,
    ) {
        $documentsPath = rtrim($documentsPath, '\\/');
        $this->documentsRoot = is_dir($documentsPath.'/documentos') ? $documentsPath.'/documentos' : $documentsPath;
    }

    public function run(): ImportReport
    {
        $this->report = new ImportReport($this->dryRun);
        $this->disk = Storage::disk('public');

        $this->step('Leyendo el respaldo SQL…');
        $this->load();
        $this->collectSources();

        ActivityLog::withoutLogging(function (): void {
            $news = $this->classifyPosts();

            $this->step('Importando documentos y convocatorias…');
            $this->importDownloads();
            $this->redirectContainerPosts();

            $this->step('Importando noticias e imágenes…');
            $this->importNews($news);

            $this->step('Actualizando unidades orgánicas desde las páginas…');
            $this->importPages();

            $this->step('Asignando logos a los enlaces de interés…');
            $this->importLinkLogos();
        });

        return $this->report;
    }

    private function load(): void
    {
        $prefix = $this->reader->detectPrefix();

        foreach ($this->reader->rows($prefix.'posts') as $row) {
            $type = $row['post_type'];
            $status = $row['post_status'];
            $keep = match ($type) {
                'post', 'page' => $status === 'publish',
                'dedo_download' => in_array($status, ['publish', 'draft'], true),
                'attachment' => true,
                default => false,
            };

            if ($keep) {
                $this->posts[(int) $row['ID']] = [
                    'id' => (int) $row['ID'],
                    'type' => $type,
                    'status' => $status,
                    'title' => (string) $row['post_title'],
                    'name' => rawurldecode((string) $row['post_name']),
                    'date' => (string) $row['post_date'],
                    'content' => $type === 'attachment' ? '' : (string) $row['post_content'],
                    'excerpt' => (string) $row['post_excerpt'],
                ];
            }
        }

        $metaKeys = ['_thumbnail_id', '_wp_attached_file', '_dedo_file_url', 'wpb_post_neder_views_count'];

        foreach ($this->reader->rows($prefix.'postmeta') as $row) {
            if (in_array($row['meta_key'], $metaKeys, true) && isset($this->posts[(int) $row['post_id']])) {
                $this->meta[(int) $row['post_id']][$row['meta_key']] = (string) $row['meta_value'];
            }
        }

        $terms = [];
        foreach ($this->reader->rows($prefix.'terms') as $row) {
            $terms[$row['term_id']] = html_entity_decode((string) $row['name']);
        }

        $taxonomies = [];
        foreach ($this->reader->rows($prefix.'term_taxonomy') as $row) {
            if (in_array($row['taxonomy'], ['category', 'ddownload_category'], true)) {
                $taxonomies[$row['term_taxonomy_id']] = $terms[$row['term_id']] ?? '';
            }
        }

        foreach ($this->reader->rows($prefix.'term_relationships') as $row) {
            if (isset($taxonomies[$row['term_taxonomy_id']], $this->posts[(int) $row['object_id']])) {
                $this->termsByPost[(int) $row['object_id']][] = $taxonomies[$row['term_taxonomy_id']];
            }
        }

        foreach ($this->reader->rows($prefix.'ddownload_statistics') as $row) {
            if ($row['status'] === 'success') {
                $this->downloadCounts[(int) $row['post_id']] = ($this->downloadCounts[(int) $row['post_id']] ?? 0) + 1;
            }
        }
    }

    private function collectSources(): void
    {
        foreach ($this->posts as $post) {
            if (! in_array($post['type'], ['post', 'page'], true) || $this->spam->isSpam($post['title'], $post['content'])) {
                continue;
            }

            preg_match_all('/\[ddownload\s[^\]]*?id="?(\d+)/i', $post['content'], $matches);

            foreach (array_unique($matches[1]) as $downloadId) {
                $this->sources[(int) $downloadId][] = ['type' => $post['type'], 'id' => $post['id'], 'title' => $post['title']];
            }
        }
    }

    /**
     * Separates spam, document-only announcements and real news.
     *
     * @return list<int>
     */
    private function classifyPosts(): array
    {
        $news = [];

        foreach ($this->posts as $post) {
            if ($post['type'] !== 'post') {
                continue;
            }

            if ($this->spam->isSpam($post['title'], $post['content'])) {
                $this->report->increment('Entradas de spam descartadas');
                $this->report->add('Spam descartado', substr($post['date'], 0, 10).' · '.Str::limit($post['title'], 110));

                continue;
            }

            preg_match_all('/\[ddownload\s[^\]]*?id="?(\d+)/i', $post['content'], $matches);
            $text = $this->plainText($post['content']);
            $textLength = mb_strlen($text);
            $isContainer = $matches[1] !== [] && ($textLength < 150 || ($textLength < 700 && $this->classifier->isDocumentAnnouncement($post['title'])));

            if (! $isContainer) {
                $news[] = $post['id'];

                continue;
            }

            $this->containerPosts[$post['id']] = array_map('intval', $matches[1]);
            $this->report->increment('Publicaciones de documentos convertidas (no son noticias)');
            $this->report->add('Publicaciones convertidas en documentos', substr($post['date'], 0, 10).' · '.$this->normalizer->title($post['title']));

            if ($textLength >= 40) {
                foreach ($matches[1] as $downloadId) {
                    $this->containerDescriptions[(int) $downloadId] ??= Str::limit($text, 1000);
                }
            }
        }

        return $news;
    }

    private function importDownloads(): void
    {
        $jobGroups = [];

        foreach ($this->posts as $download) {
            if ($download['type'] !== 'dedo_download') {
                continue;
            }

            if ($download['status'] !== 'publish') {
                $this->report->increment('Descargas en borrador omitidas');

                continue;
            }

            $title = $this->normalizer->title($download['title']) ?: $this->titleFromFile($download['id']);

            if (preg_match('/^(prueba|test)\s*\d*$/iu', $title)) {
                $this->report->add('Descargas de prueba omitidas', "#{$download['id']} {$title}");

                continue;
            }

            if ($this->spam->isSpam($title)) {
                $this->report->increment('Entradas de spam descartadas');
                $this->report->add('Spam descartado', 'Descarga #'.$download['id'].' · '.$title);

                continue;
            }

            if ($this->normalizer->hasHomoglyphs($download['title'])) {
                $this->report->add('Títulos con letras cirílicas corregidas (revisar)', "#{$download['id']} {$title}");
            }

            $sourceTitles = array_column($this->sources[$download['id']] ?? [], 'title');
            $type = $this->classifier->classify($title, $sourceTitles, $this->termsByPost[$download['id']] ?? []);

            if ($type === DocumentClassifier::JOB_POSTING) {
                [$key, $group] = $this->jobPostingGroup($download, $title);
                $jobGroups[$key] ??= $group;
                $jobGroups[$key]['files'][] = ['id' => $download['id'], 'title' => $title, 'date' => $download['date']];
                $jobGroups[$key]['posts'] = array_values(array_unique([...$jobGroups[$key]['posts'], ...$group['posts']]));

                if (mb_strlen($group['code']) > mb_strlen($jobGroups[$key]['code']) && str_starts_with($group['code'], 'CAS')) {
                    $jobGroups[$key]['code'] = $group['code'];
                }

                continue;
            }

            $this->importDocument($download, $title, $type);
        }

        foreach ($jobGroups as $key => $group) {
            $this->importJobPosting($key, $group);
        }
    }

    /**
     * @param  array{id: int, title: string, date: string}  $download
     */
    private function importDocument(array $download, string $title, string $typeName): void
    {
        $source = $this->locateDownloadFile($download['id']);
        $stored = $source ? $this->storeFile($source, 'documentos/'.substr($download['date'], 0, 4)) : null;

        if ($source === null) {
            $this->report->add('Documentos sin archivo (quedan como no publicados)', "#{$download['id']} {$title}");
        }

        $this->report->increment('Documentos: '.$typeName);

        if ($typeName === 'Otros documentos') {
            $this->report->add('Documentos sin clasificar (tipo «Otros documentos», revisar)', substr($download['date'], 0, 10).' · '.$title);
        }

        if ($this->dryRun) {
            $this->downloadTargets[$download['id']] = ['url' => '/documentos', 'title' => $title];

            return;
        }

        $type = DocumentType::query()->firstOrCreate(['name' => $typeName], ['section' => 'gestion']);
        $acronym = $this->classifier->unitAcronymFor($typeName, $title);
        $document = Document::query()->where('wp_id', $download['id'])->first() ?? new Document;

        $document->forceFill([
            'wp_id' => $download['id'],
            'document_type_id' => $type->id,
            'organizational_unit_id' => $acronym ? $this->unitId(acronym: $acronym) : null,
            'title' => $title,
            'code' => $this->documentCode($title),
            'description' => $document->description ?? $this->containerDescriptions[$download['id']] ?? null,
            'issued_at' => substr($download['date'], 0, 10),
            'file_path' => $stored['path'] ?? null,
            'file_name' => $source ? basename($source) : null,
            'file_hash' => $stored['hash'] ?? null,
            'downloads' => $this->downloadCounts[$download['id']] ?? 0,
            'is_published' => $stored !== null,
        ])->save();

        $this->downloadTargets[$download['id']] = ['url' => route('documents.show', $document, false), 'title' => $title];
        $this->redirect(Redirect::downloadPath($download['id']), route('documents.download', $document, false));
    }

    /**
     * @param  array{id: int, title: string, date: string}  $download
     * @return array{0: string, 1: array{code: string, title: string, modality: string, date: string, posts: list<int>, files: list<array{id: int, title: string, date: string}>}}
     */
    private function jobPostingGroup(array $download, string $title): array
    {
        $jobSources = array_values(array_filter(
            $this->sources[$download['id']] ?? [],
            fn (array $source): bool => $source['type'] === 'post' && $this->classifier->isJobPostingTitle($source['title']),
        ));
        $sourceTitle = isset($jobSources[0]) ? $this->normalizer->title($jobSources[0]['title']) : null;
        $year = substr($download['date'], 0, 4);
        $code = $this->jobPostingCode($sourceTitle ?? '', $year) ?? $this->jobPostingCode($title, $year);
        $text = Str::upper(Str::ascii(($sourceTitle ?? '').' '.$title));
        $modality = match (true) {
            str_contains($text, 'SUPLENCIA') => 'CAS por suplencia',
            str_contains($text, 'PRACTICA') => 'Prácticas preprofesionales',
            default => 'CAS',
        };

        $key = match (true) {
            $code !== null => 'cas-'.Str::slug($code['key']),
            isset($jobSources[0]) => 'wp-post-'.$jobSources[0]['id'],
            default => 'wp-download-'.$download['id'],
        };

        return [$key, [
            'code' => $code['label'] ?? ($sourceTitle ?? $title),
            'title' => $sourceTitle ?? $title,
            'modality' => $modality,
            'date' => $download['date'],
            'posts' => array_column($jobSources, 'id'),
            'files' => [],
        ]];
    }

    /**
     * @return array{key: string, label: string}|null
     */
    private function jobPostingCode(string $text, string $fallbackYear): ?array
    {
        $ascii = Str::upper(Str::ascii($text));

        if (! preg_match('/\bCAST?\b(?:\s+POR\s+SUPLENCIA)?[^0-9]{0,25}?0*(\d{1,3})(?!\d)(?:\s*[-–\s]\s*(20\d{2}))?(\s*-?\s*GOREMAD[\w\/-]*)?/u', $ascii, $match)) {
            return null;
        }

        $number = str_pad($match[1], 3, '0', STR_PAD_LEFT);
        $year = ($match[2] ?? '') !== '' ? $match[2] : $fallbackYear;
        $suffix = ($match[3] ?? '') !== '' ? '-'.ltrim(preg_replace('/\s+/', '', $match[3]) ?? '', '-') : '';
        $prefix = str_contains($ascii, 'SUPLENCIA') ? 'CAS por suplencia' : 'CAS';

        return [
            'key' => "{$prefix} {$number}-{$year}",
            'label' => "{$prefix} N° {$number}-{$year}{$suffix}",
        ];
    }

    /**
     * @param  array{code: string, title: string, modality: string, date: string, posts: list<int>, files: list<array{id: int, title: string, date: string}>}  $group
     */
    private function importJobPosting(string $key, array $group): void
    {
        usort($group['files'], fn (array $a, array $b): int => strcmp($a['date'], $b['date']));
        $startsAt = Carbon::parse($group['files'][0]['date']);
        $this->report->increment('Convocatorias creadas');
        $this->report->increment('Archivos de convocatorias', count($group['files']));
        $this->report->add('Convocatorias', sprintf('%s · %s (%d archivos)', $startsAt->format('Y-m-d'), $group['code'], count($group['files'])));

        $stored = [];
        foreach ($group['files'] as $file) {
            $source = $this->locateDownloadFile($file['id']);
            $stored[$file['id']] = $source ? $this->storeFile($source, 'convocatorias/'.substr($file['date'], 0, 4)) : null;

            if ($source === null) {
                $this->report->add('Archivos de convocatorias no encontrados', "#{$file['id']} {$file['title']}");
            }
        }

        if ($this->dryRun) {
            foreach ($group['files'] as $file) {
                $this->downloadTargets[$file['id']] = ['url' => '/convocatorias', 'title' => $file['title']];
            }

            return;
        }

        $posting = JobPosting::query()->where('import_key', $key)->first() ?? new JobPosting;
        $posting->forceFill([
            'import_key' => $key,
            'code' => Str::limit($group['code'], 250, ''),
            'title' => Str::limit($group['title'], 250, ''),
            'modality' => $group['modality'],
            'status' => $posting->status ?? ($startsAt->greaterThan(now()->subDays(45)) ? JobPostingStatus::EnCurso : JobPostingStatus::Finalizada),
            'starts_at' => $startsAt->toDateString(),
            'organizational_unit_id' => $this->unitId(name: 'Unidad de Personal'),
            'is_published' => true,
        ])->save();

        foreach ($group['files'] as $order => $file) {
            $record = JobPostingFile::query()->where('wp_id', $file['id'])->first() ?? new JobPostingFile;
            $record->forceFill([
                'wp_id' => $file['id'],
                'job_posting_id' => $posting->id,
                'label' => Str::limit($file['title'], 250, ''),
                'file_path' => $stored[$file['id']]['path'] ?? null,
                'published_at' => $file['date'],
                'sort_order' => $order,
            ])->save();

            $url = route('job-postings.show', $posting, false);
            $this->downloadTargets[$file['id']] = ['url' => $url, 'title' => $file['title']];
            $this->redirect(Redirect::downloadPath($file['id']), $record->file_path ? '/storage/'.$record->file_path : $url);
        }

        foreach ($group['posts'] as $postId) {
            $this->redirect(Redirect::normalizePath('/'.$this->posts[$postId]['name']), route('job-postings.show', $posting, false));
        }
    }

    /**
     * Old announcement posts now point to the document or job posting they published.
     */
    private function redirectContainerPosts(): void
    {
        foreach ($this->containerPosts as $postId => $downloadIds) {
            foreach ($downloadIds as $downloadId) {
                if (isset($this->downloadTargets[$downloadId])) {
                    $this->redirect(Redirect::normalizePath('/'.$this->posts[$postId]['name']), $this->downloadTargets[$downloadId]['url']);

                    break;
                }
            }
        }
    }

    /**
     * @param  list<int>  $postIds
     */
    private function importNews(array $postIds): void
    {
        $categories = Category::query()->pluck('id', 'name');

        foreach ($postIds as $postId) {
            $post = $this->posts[$postId];
            $cleaned = $this->cleaner->clean(
                $post['content'],
                fn (int $id): ?array => $this->downloadTargets[$id] ?? null,
                fn (string $url): ?string => $this->imageUrl($url),
            );
            $title = $this->normalizer->title($post['title'])
                ?: $this->normalizer->title(Str::limit(Str::before($cleaned['text'], '. '), 110, ''))
                ?: 'Noticia del '.Carbon::parse($post['date'])->format('d/m/Y');

            $gallery = array_values(array_filter(array_map(fn (int $id): ?string => $this->attachmentImage($id), $cleaned['gallery'])));
            $cover = isset($this->meta[$postId]['_thumbnail_id']) ? $this->attachmentImage((int) $this->meta[$postId]['_thumbnail_id']) : null;
            $cover ??= $gallery[0] ?? null;
            $gallery = array_values(array_diff($gallery, [$cover]));
            $categoryName = $this->newsCategory($title);

            $this->report->increment('Noticias importadas');
            $this->report->increment('Noticias: '.$categoryName);
            $this->report->add('Noticias importadas', substr($post['date'], 0, 10).' · ['.$categoryName.'] '.$title);

            if ($cover !== null) {
                $this->report->increment('Noticias con imagen de portada');
            } else {
                $this->report->add('Noticias sin imagen', $title);
            }

            if ($this->dryRun) {
                continue;
            }

            $model = Post::query()->where('wp_id', $postId)->first() ?? new Post;
            $slugSource = Str::slug($post['name']) ?: Str::slug($title);

            $model->forceFill([
                'wp_id' => $postId,
                'category_id' => $categories[$categoryName] ?? null,
                'title' => $title,
                'slug' => $model->exists ? $model->slug : Post::generateUniqueSlug($slugSource),
                'excerpt' => trim($post['excerpt']) !== '' ? Str::limit(strip_tags($post['excerpt']), 400) : Str::limit($cleaned['text'], 220),
                'body' => $cleaned['html'],
                'cover_image' => $cover,
                'gallery' => $gallery ?: null,
                'is_published' => true,
                'published_at' => $post['date'],
                'views' => (int) ($this->meta[$postId]['wpb_post_neder_views_count'] ?? 0),
            ])->save();

            $this->redirect(Redirect::normalizePath('/'.$post['name']), route('posts.show', $model, false));
        }
    }

    private function importPages(): void
    {
        foreach ($this->posts as $page) {
            if ($page['type'] !== 'page') {
                continue;
            }

            $slug = Str::lower($page['name']);

            if (isset(self::PAGE_REDIRECTS[$slug])) {
                $this->redirect(Redirect::normalizePath('/'.$slug), self::PAGE_REDIRECTS[$slug]);
            }

            if ($slug === 'agencia-agraria-tambopata') {
                $this->importAgencyPage($page);

                continue;
            }

            if (! isset(self::UNIT_PAGES[$slug])) {
                continue;
            }

            $cleaned = $this->cleaner->clean($page['content'], fn (): ?array => null, fn (): ?string => null);

            if ($cleaned['text'] === '') {
                continue;
            }

            [$description, $functions] = $this->splitDescription($cleaned['html']);
            $this->report->increment('Unidades orgánicas actualizadas con el texto del portal anterior');

            if ($this->dryRun) {
                continue;
            }

            $unit = OrganizationalUnit::query()->where('name', self::UNIT_PAGES[$slug])->first();

            if ($unit === null) {
                $this->report->add('Páginas sin unidad equivalente', $page['title']);

                continue;
            }

            $unit->forceFill([
                'description' => $description ?? $unit->description,
                'functions' => $functions ?: $unit->functions,
            ])->save();

            $this->redirect(Redirect::normalizePath('/'.$slug), route('institutional.unit', $unit, false));
        }
    }

    /**
     * @param  array{title: string, content: string, name: string}  $page
     */
    /**
     * Only links still without a logo get one, so logos uploaded in the panel are never replaced.
     */
    private function importLinkLogos(): void
    {
        foreach (self::LINK_LOGOS as $name => $relative) {
            $link = Link::query()->where('name', $name)->whereNull('logo')->first();
            $source = $this->imagesPath ? rtrim($this->imagesPath, '\\/').'/'.$relative : null;

            if (! $link || ! $source || ! is_file($source)) {
                continue;
            }

            $path = 'enlaces/'.Str::slug($name).'.'.strtolower(pathinfo($relative, PATHINFO_EXTENSION));

            if (! $this->dryRun) {
                $this->disk->put($path, (string) file_get_contents($source));
                $link->update(['logo' => $path]);
            }

            $this->report->increment('Logos de enlaces asignados');
        }
    }

    private function importAgencyPage(array $page): void
    {
        $cleaned = $this->cleaner->clean($page['content'], fn (): ?array => null, fn (): ?string => null);
        $agency = Agency::query()->where('name', 'Agencia Agraria Tambopata')->first();

        if ($this->dryRun || $agency === null || $cleaned['html'] === '') {
            return;
        }

        $agency->forceFill(['description' => $cleaned['html']])->save();
        $this->redirect(Redirect::normalizePath('/'.$page['name']), route('agencies.show', $agency, false));
        $this->report->increment('Agencias actualizadas con el texto del portal anterior');
    }

    /**
     * @return array{0: string|null, 1: string}
     */
    private function splitDescription(string $html): array
    {
        $blocks = array_values(array_filter(explode("\n", $html), fn (string $block): bool => ! preg_match('/designaci|desiganaci/iu', $block)));
        $description = null;

        foreach ($blocks as $index => $block) {
            $text = trim(strip_tags($block));

            if (str_starts_with($block, '<p>') && mb_strlen($text) >= 60) {
                $description = Str::limit(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'), 1000);
                unset($blocks[$index]);
                break;
            }
        }

        return [$description, trim(implode("\n", $blocks))];
    }

    private function newsCategory(string $title): string
    {
        $text = Str::upper(Str::ascii($title));

        return match (true) {
            str_starts_with($text, 'COMUNICADO') => 'Comunicados',
            (bool) preg_match('/CAMPANA|VACUNA|SANITARI|FUMIGA|DESPARASIT|CARBUNCO|FIEBRE AFTOSA/', $text) => 'Campañas',
            (bool) preg_match('/FERIA|EXPO|FESTIVAL|ANIVERSARIO|CONCURSO|EVENTO|FESTIVIDAD/', $text) => 'Ferias y eventos',
            default => 'Noticias institucionales',
        };
    }

    private function titleFromFile(int $downloadId): string
    {
        $file = $this->locateDownloadFile($downloadId);
        $name = $file ? str_replace(['-', '_'], ' ', pathinfo($file, PATHINFO_FILENAME)) : '';

        return $this->normalizer->title($name) ?: 'Documento '.$downloadId;
    }

    private function locateDownloadFile(int $downloadId): ?string
    {
        $url = $this->meta[$downloadId]['_dedo_file_url'] ?? '';
        $position = stripos($url, 'wp-content/uploads/');

        if ($position === false) {
            return null;
        }

        $relative = rawurldecode(substr($url, $position + strlen('wp-content/uploads/')));
        $relative = strtok($relative, '?') ?: $relative;
        $path = $this->documentsRoot.'/'.$relative;

        return is_file($path) ? $path : null;
    }

    /**
     * Copies a file into the public disk, reusing identical files already stored.
     *
     * @return array{path: string, hash: string}
     */
    private function storeFile(string $source, string $directory): array
    {
        $hash = (string) md5_file($source);

        if (isset($this->storedFiles[$hash])) {
            $this->report->increment('Archivos duplicados reutilizados');

            return ['path' => $this->storedFiles[$hash], 'hash' => $hash];
        }

        $extension = Str::lower(pathinfo($source, PATHINFO_EXTENSION)) ?: 'pdf';
        $name = Str::limit(Str::slug(pathinfo($source, PATHINFO_FILENAME)), 100, '') ?: 'documento';
        $path = "{$directory}/{$name}.{$extension}";
        $suffix = 2;

        while ($this->disk->exists($path) && md5_file($this->disk->path($path)) !== $hash) {
            $path = "{$directory}/{$name}-{$suffix}.{$extension}";
            $suffix++;
        }

        if (! $this->dryRun && ! $this->disk->exists($path)) {
            $stream = fopen($source, 'rb');
            $this->disk->writeStream($path, $stream);
            is_resource($stream) && fclose($stream);
            $this->report->bytesCopied += (int) filesize($source);
        }

        $this->report->increment('Archivos almacenados');
        $this->storedFiles[$hash] = $path;

        return ['path' => $path, 'hash' => $hash];
    }

    private function attachmentImage(int $attachmentId): ?string
    {
        $relative = $this->meta[$attachmentId]['_wp_attached_file'] ?? null;

        return $relative ? $this->storeImage($relative) : null;
    }

    private function imageUrl(string $url): ?string
    {
        $position = stripos($url, 'wp-content/uploads/');

        if ($position === false) {
            return null;
        }

        $relative = rawurldecode(strtok(substr($url, $position + strlen('wp-content/uploads/')), '?') ?: '');
        $relative = (string) preg_replace('/-\d+x\d+(?=\.\w+$)/', '', $relative);
        $stored = $this->storeImage($relative);

        return $stored ? '/storage/'.$stored : null;
    }

    private function storeImage(string $relative): ?string
    {
        if (array_key_exists($relative, $this->storedImages)) {
            return $this->storedImages[$relative];
        }

        $source = $this->imagesPath ? rtrim($this->imagesPath, '\\/').'/'.$relative : null;
        $info = $source && is_file($source) ? @getimagesize($source) : false;

        if ($info === false) {
            $this->report->increment('Imágenes no encontradas');

            return $this->storedImages[$relative] = null;
        }

        $extension = match ($info[2]) {
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_WEBP => 'webp',
            default => 'jpg',
        };
        $folder = trim(str_replace('\\', '/', dirname($relative)), './');
        $directory = 'noticias'.($folder !== '' ? '/'.$folder : '');
        $path = $directory.'/'.(Str::limit(Str::slug(pathinfo($relative, PATHINFO_FILENAME)), 100, '') ?: 'imagen').'.'.$extension;

        if (! $this->dryRun && ! $this->disk->exists($path)) {
            $this->disk->put($path, $this->resizedImage($source, $info));
            $this->report->bytesCopied += (int) $this->disk->size($path);
        }

        $this->report->increment('Imágenes almacenadas');

        return $this->storedImages[$relative] = $path;
    }

    /**
     * Downscales large JPEG/PNG photos so pages load fast; other images are copied as is.
     *
     * @param  array{0: int, 1: int, 2: int}  $info
     */
    private function resizedImage(string $source, array $info): string
    {
        [$width, , $type] = $info;

        if ($width <= self::IMAGE_MAX_WIDTH || ! function_exists('imagescale') || ! in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            return (string) file_get_contents($source);
        }

        $image = $type === IMAGETYPE_PNG ? @imagecreatefrompng($source) : @imagecreatefromjpeg($source);

        if ($image === false) {
            return (string) file_get_contents($source);
        }

        $scaled = imagescale($image, self::IMAGE_MAX_WIDTH);

        if ($scaled === false) {
            return (string) file_get_contents($source);
        }

        ob_start();
        $type === IMAGETYPE_PNG ? imagepng($scaled, null, 8) : imagejpeg($scaled, null, 82);

        return (string) ob_get_clean();
    }

    private function documentCode(string $title): ?string
    {
        if (preg_match('/N\s*[°º]\s*0*\d{1,4}\s*-\s*20\d{2}[\w\/\-]*/u', $title, $match)) {
            $prefix = preg_match('/^resoluci[oó]n directoral regional/iu', $title) ? 'RDR' : (preg_match('/^resoluci[oó]n/iu', $title) ? 'RD' : null);

            return trim(($prefix ? $prefix.' ' : '').preg_replace('/\s+/', '', $match[0]));
        }

        return null;
    }

    private function unitId(?string $acronym = null, ?string $name = null): ?int
    {
        $key = $acronym ?? $name;

        return $this->unitIds[$key] ??= OrganizationalUnit::query()
            ->when($acronym, fn ($query) => $query->where('acronym', $acronym))
            ->when($name, fn ($query) => $query->where('name', $name))
            ->value('id');
    }

    private function redirect(string $oldPath, string $newPath): void
    {
        if ($this->dryRun || $oldPath === '/' || $oldPath === $newPath) {
            return;
        }

        Redirect::query()->updateOrCreate(['old_path' => $oldPath], ['new_path' => $newPath]);
    }

    private function plainText(string $content): string
    {
        $text = (string) preg_replace('/\[[^\]]*\]/', ' ', $content);

        return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    private function step(string $message): void
    {
        if ($this->progress) {
            ($this->progress)($message);
        }
    }
}
