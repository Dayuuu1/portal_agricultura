<?php

namespace App\Enums;

/**
 * Something a visitor did inside a page, reported by resources/js/analytics.js.
 */
enum SiteEventType: string
{
    /** A search on the portal, the news or the documents, with its number of results. */
    case Search = 'search';

    /** A click on the download link of a document. */
    case Download = 'download';

    /** A click on a link to another website. */
    case Outbound = 'outbound';

    /** A visit to an address that does not exist (an old or mistyped link). */
    case NotFound = 'not_found';
}
