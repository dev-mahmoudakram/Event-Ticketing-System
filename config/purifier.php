<?php

/**
 * HTML Purifier configuration for CMS rich-text fields.
 *
 * This is the actual security boundary for rich text in this application — CKEditor is
 * configured to only expose a small set of formatting plugins, but that is a UX constraint,
 * not a trust boundary: a request can be crafted by hand to post any HTML at all, bypassing
 * the editor entirely. Every rich-text field is purified server-side, on every write, against
 * the strict allowlist below, before it is ever stored.
 *
 * 'cms' is the only profile in real use. It intentionally does not offer 'style', 'id',
 * 'class', iframes, scripts, forms, or event-handler attributes of any kind — HTML Purifier
 * already strips those unconditionally, since they are never listed in HTML.Allowed, but
 * spelling that out here matters for anyone auditing this file later:
 *   - no <script>, <iframe>, <object>, <embed>, <form>, <style>
 *   - no on*="" event handlers on any element
 *   - no inline style="" (blocks CSS-based injection and disallowed CSS entirely)
 *   - links only through http/https/mailto (URI.AllowedSchemes below); "javascript:" and
 *     other schemes are rejected by HTML Purifier's own URI filtering before the tag survives
 */
return [
    'encoding' => 'UTF-8',
    'finalize' => true,
    'ignoreNonStrings' => false,
    'cachePath' => storage_path('app/purifier'),
    'cacheFileMode' => 0755,

    'settings' => [
        'cms' => [
            'HTML.Doctype' => 'XHTML 1.0 Strict',
            // Exactly what the CKEditor build offers: paragraphs, headings, bold/italic,
            // links, lists, and blockquotes. Nothing else is allowed to survive.
            'HTML.Allowed' => 'p,br,strong,b,em,i,h2,h3,h4,ul,ol,li,a[href],blockquote',
            'HTML.ForbiddenAttributes' => 'style,class,id,on*',
            'CSS.AllowedProperties' => '',
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.RemoveEmpty' => true,
            'URI.AllowedSchemes' => ['http' => true, 'https' => true, 'mailto' => true],
            'URI.DisableExternalResources' => false,
            'Attr.AllowedFrameTargets' => [],
            'Attr.EnableID' => false,
            'HTML.TargetBlank' => false,
        ],
    ],
];
