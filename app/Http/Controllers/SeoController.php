<?php

namespace App\Http\Controllers;

use App\Support\Seo\SearchEngines;
use App\Support\Seo\Sitemap;
use Illuminate\Http\Response;

/**
 * The files search engines read: robots.txt (what to crawl and where the sitemap is) and the sitemaps.
 */
class SeoController extends Controller
{
    public function robots(): Response
    {
        return response(SearchEngines::robotsTxt(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        return $this->xml(Sitemap::indexXml());
    }

    public function section(string $section): Response
    {
        return $this->xml(Sitemap::sectionXml($section));
    }

    private function xml(string $content): Response
    {
        return response($content, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
