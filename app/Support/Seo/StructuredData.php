<?php

namespace App\Support\Seo;

use App\Enums\JobPostingStatus;
use App\Models\JobPosting;
use App\Models\Post;
use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Schema.org descriptions (JSON-LD) that let Google show the entity, its news and its open job postings with
 * their own look in the results: name and logo of the institution, news with photo and date, and CAS postings
 * in Google's job search.
 */
final class StructuredData
{
    private static function organizationId(): string
    {
        return route('home').'#organizacion';
    }

    /**
     * The entity and the site, on every page.
     *
     * @return list<array<string, mixed>>
     */
    public static function site(): array
    {
        $name = Setting::valueOf('site_name') ?? config('app.name');

        return [
            array_filter([
                '@type' => 'GovernmentOrganization',
                '@id' => self::organizationId(),
                'name' => $name,
                'alternateName' => array_values(array_filter([Setting::valueOf('site_short_name'), Setting::valueOf('site_acronym')])),
                'url' => route('home'),
                'logo' => Setting::logoUrl(),
                'description' => Setting::valueOf('site_description'),
                'email' => Setting::valueOf('email'),
                'telephone' => Setting::valueOf('phone_main'),
                'address' => array_filter([
                    '@type' => 'PostalAddress',
                    'streetAddress' => Setting::valueOf('address'),
                    'addressLocality' => 'Puerto Maldonado',
                    'addressRegion' => 'Madre de Dios',
                    'addressCountry' => 'PE',
                ]),
                'parentOrganization' => filled(Setting::valueOf('parent_entity'))
                    ? ['@type' => 'GovernmentOrganization', 'name' => Setting::valueOf('parent_entity')]
                    : null,
                'sameAs' => array_values(array_filter([Setting::valueOf('facebook_url'), Setting::valueOf('youtube_url')])),
            ]),
            [
                '@type' => 'WebSite',
                '@id' => route('home').'#sitio',
                'url' => route('home'),
                'name' => $name,
                'alternateName' => array_values(array_filter([Setting::valueOf('site_short_name'), Setting::valueOf('site_acronym')])),
                'inLanguage' => 'es-PE',
                'publisher' => ['@id' => self::organizationId()],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('search').'?q={search_term_string}'],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function newsArticle(Post $post): array
    {
        return array_filter([
            '@type' => 'NewsArticle',
            'headline' => Str::limit($post->title, 110, '…'),
            'description' => $post->summary,
            'image' => [$post->cover_url ?? Setting::logoUrl()],
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => ($post->updated_at ?? $post->published_at)?->toIso8601String(),
            'articleSection' => $post->category?->name,
            'inLanguage' => 'es-PE',
            'mainEntityOfPage' => route('posts.show', $post),
            'author' => ['@id' => self::organizationId()],
            'publisher' => ['@id' => self::organizationId()],
        ]);
    }

    /**
     * Only open postings go to Google's job search: Google asks to remove the markup once a posting closes.
     *
     * @return array<string, mixed>|null
     */
    public static function jobPosting(JobPosting $jobPosting): ?array
    {
        if (! $jobPosting->is_published || $jobPosting->status !== JobPostingStatus::EnCurso || $jobPosting->ends_at?->endOfDay()->isPast()) {
            return null;
        }

        // The rich text of the panel, cleaned like on the posting's page (Google accepts HTML in the description).
        $description = filled(strip_tags((string) $jobPosting->description))
            ? (string) str($jobPosting->description)->sanitizeHtml()
            : '<p>'.e('Convocatoria '.$jobPosting->code.': '.$jobPosting->title.'. Las bases, los requisitos y el cronograma están publicados en el portal.').'</p>';

        return array_filter([
            '@type' => 'JobPosting',
            'title' => $jobPosting->title,
            'description' => $description,
            'identifier' => ['@type' => 'PropertyValue', 'name' => Setting::valueOf('site_acronym') ?? 'DRDAR', 'value' => $jobPosting->code],
            'datePosted' => ($jobPosting->starts_at ?? $jobPosting->created_at)?->toDateString(),
            'validThrough' => $jobPosting->ends_at?->endOfDay()->toIso8601String(),
            'employmentType' => 'FULL_TIME',
            'totalJobOpenings' => $jobPosting->positions_count ?: null,
            'hiringOrganization' => [
                '@type' => 'GovernmentOrganization',
                'name' => Setting::valueOf('site_name') ?? config('app.name'),
                'sameAs' => route('home'),
                'logo' => Setting::logoUrl(),
            ],
            'jobLocation' => [
                '@type' => 'Place',
                'address' => array_filter([
                    '@type' => 'PostalAddress',
                    'streetAddress' => Setting::valueOf('address'),
                    'addressLocality' => 'Puerto Maldonado',
                    'addressRegion' => 'Madre de Dios',
                    'addressCountry' => 'PE',
                ]),
            ],
            'directApply' => false,
            'url' => route('job-postings.show', $jobPosting),
        ]);
    }

    /**
     * The breadcrumb shown above the title, so results show «Inicio › Documentos › …» instead of the bare address.
     *
     * @param  array<string, string|null>  $breadcrumbs  Label => address (null for the current page).
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $breadcrumbs): array
    {
        $items = [['Inicio', route('home')]];

        foreach ($breadcrumbs as $label => $url) {
            $items[] = [(string) $label, $url ?? url()->current()];
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $item, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item[0],
                'item' => $item[1],
            ], $items, array_keys($items)),
        ];
    }

    /**
     * JSON for a <script type="application/ld+json">: «<», «>» and «&» are escaped so no text can close the tag.
     *
     * @param  list<array<string, mixed>>|array<string, mixed>  $data
     */
    public static function toJson(array $data): string
    {
        $graph = array_is_list($data) ? $data : [$data];

        return json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
        );
    }
}
