<?php

namespace App\Support\Seo;

use App\Models\Agency;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\JobPosting;
use App\Models\OrganizationalUnit;
use App\Models\Page;
use App\Models\Post;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use XMLWriter;

/**
 * The sitemaps given to Google Search Console: an index (/sitemap.xml) and one file per kind of content, so Search
 * Console reports how much of each part of the portal is indexed. Only published content is listed.
 */
final class Sitemap
{
    /**
     * Section => title. The key is the file name: /sitemaps/{key}.xml.
     */
    public const SECTIONS = [
        'paginas' => 'Páginas institucionales y listados',
        'noticias' => 'Noticias',
        'documentos' => 'Documentos',
        'convocatorias' => 'Convocatorias',
    ];

    /**
     * Search engines read the sitemap a few times a day at most; an hour of cache keeps it cheap and fresh enough.
     */
    private const CACHE_MINUTES = 60;

    public static function indexXml(): string
    {
        return Cache::remember('seo:sitemap:index', now()->addMinutes(self::CACHE_MINUTES), function (): string {
            $xml = self::start('sitemapindex');

            foreach (array_keys(self::SECTIONS) as $section) {
                $xml->startElement('sitemap');
                $xml->writeElement('loc', route('seo.sitemap.section', $section));

                if ($lastModified = self::lastModified($section)) {
                    $xml->writeElement('lastmod', $lastModified->toAtomString());
                }

                $xml->endElement();
            }

            return self::finish($xml);
        });
    }

    public static function sectionXml(string $section): string
    {
        return Cache::remember('seo:sitemap:'.$section, now()->addMinutes(self::CACHE_MINUTES), function () use ($section): string {
            $xml = self::start('urlset', withImages: $section === 'noticias');

            foreach (self::entries($section) as $entry) {
                $xml->startElement('url');
                $xml->writeElement('loc', $entry['loc']);

                if ($entry['lastmod'] ?? null) {
                    $xml->writeElement('lastmod', $entry['lastmod']->toAtomString());
                }

                if ($entry['image'] ?? null) {
                    $xml->startElement('image:image');
                    $xml->writeElement('image:loc', $entry['image']);
                    $xml->endElement();
                }

                $xml->endElement();
            }

            return self::finish($xml);
        });
    }

    /**
     * @return list<array{loc: string, lastmod?: ?CarbonInterface, image?: ?string}>
     */
    public static function entries(string $section): array
    {
        return match ($section) {
            'paginas' => self::pages(),
            'noticias' => Post::query()->published()->latest('published_at')->get(['id', 'slug', 'cover_image', 'updated_at', 'published_at'])
                ->map(fn (Post $post): array => ['loc' => route('posts.show', $post), 'lastmod' => $post->updated_at ?? $post->published_at, 'image' => $post->cover_url])
                ->all(),
            'documentos' => Document::query()->published()->latest('issued_at')->get(['id', 'slug', 'updated_at'])
                ->map(fn (Document $document): array => ['loc' => route('documents.show', $document), 'lastmod' => $document->updated_at])
                ->all(),
            'convocatorias' => JobPosting::query()->published()->latest('starts_at')->get(['id', 'slug', 'updated_at'])
                ->map(fn (JobPosting $jobPosting): array => ['loc' => route('job-postings.show', $jobPosting), 'lastmod' => $jobPosting->updated_at])
                ->all(),
            default => [],
        };
    }

    /**
     * Fixed pages of the portal, informative pages, organizational units, agencies and the listing of each
     * document type (a page of its own for searches like «resoluciones directorales»).
     *
     * @return list<array{loc: string, lastmod?: ?CarbonInterface}>
     */
    private static function pages(): array
    {
        $fixed = ['home', 'posts.index', 'documents.index', 'agrarian-information', 'job-postings.index', 'institutional.about',
            'institutional.organization-chart', 'institutional.directory', 'agencies.index', 'transparency', 'mesa-partes.index',
            'contact.create', 'complaints.create'];

        return [
            ...array_map(fn (string $route): array => ['loc' => route($route)], $fixed),
            ...Page::query()->published()->get(['id', 'slug', 'updated_at'])
                ->map(fn (Page $page): array => ['loc' => route('pages.show', $page), 'lastmod' => $page->updated_at])->all(),
            ...OrganizationalUnit::query()->active()->orderBy('name')->get(['id', 'slug', 'updated_at'])
                ->map(fn (OrganizationalUnit $unit): array => ['loc' => route('institutional.unit', $unit), 'lastmod' => $unit->updated_at])->all(),
            ...Agency::query()->active()->orderBy('sort_order')->get(['id', 'slug', 'updated_at'])
                ->map(fn (Agency $agency): array => ['loc' => route('agencies.show', $agency), 'lastmod' => $agency->updated_at])->all(),
            ...DocumentType::query()->whereHas('documents', fn ($query) => $query->published())->orderBy('sort_order')->get()
                ->map(fn (DocumentType $type): array => ['loc' => $type->publicUrl()])->all(),
        ];
    }

    private static function lastModified(string $section): ?CarbonInterface
    {
        $model = match ($section) {
            'noticias' => Post::query()->published(),
            'documentos' => Document::query()->published(),
            'convocatorias' => JobPosting::query()->published(),
            default => null,
        };

        $value = $model?->max('updated_at');

        return $value ? Carbon::parse($value) : null;
    }

    private static function start(string $root, bool $withImages = false): XMLWriter
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement($root);
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        if ($withImages) {
            $xml->writeAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
        }

        return $xml;
    }

    private static function finish(XMLWriter $xml): string
    {
        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    /**
     * Content was published or changed: the next request rebuilds the sitemaps.
     */
    public static function flush(): void
    {
        Cache::forget('seo:sitemap:index');

        foreach (array_keys(self::SECTIONS) as $section) {
            Cache::forget('seo:sitemap:'.$section);
        }
    }
}
