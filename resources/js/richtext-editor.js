/**
 * CKEditor 5 for CMS body fields, attached to every `<textarea data-richtext>` in the admin.
 *
 * This is a UX layer, not the security boundary — the plugin list below limits what the
 * editor UI can produce, but a request is not the editor: someone can post any HTML by hand,
 * bypassing this entirely. The actual boundary is server-side, in App\Support\RichText
 * (config/purifier.php, profile "cms"), which sanitizes on every write regardless of what
 * reached the server. The two are kept in lockstep on purpose: this plugin list only offers
 * paragraphs, headings, bold/italic, links, lists and blockquotes — exactly what the "cms"
 * Purifier profile allows through — so nothing a user builds in the editor is silently
 * stripped on save. General HTML Support is deliberately not part of this build: it exists in
 * the ckeditor5 package but would let arbitrary tags/attributes through the editor itself,
 * which is the opposite of what a strict allowlist is for.
 *
 * ckeditor5's own CSS ships through the same import so the toolbar and content area render
 * without a separate <link>; Vite bundles it like any other imported stylesheet.
 */
const instances = new WeakMap();

async function attach(textarea) {
    // Imported dynamically, and only once a page actually has a rich-text field: CKEditor is
    // the single heaviest dependency in this app, and every public page (and most admin
    // pages) has no textarea to attach it to at all.
    const {
        ClassicEditor,
        Essentials,
        Paragraph,
        Heading,
        Bold,
        Italic,
        Link,
        List,
        BlockQuote,
    } = await import('ckeditor5');
    await import('ckeditor5/ckeditor5.css');

    const editor = await ClassicEditor.create(textarea, {
        licenseKey: 'GPL',
        plugins: [Essentials, Paragraph, Heading, Bold, Italic, Link, List, BlockQuote],
        toolbar: ['heading', '|', 'bold', 'italic', 'link', '|', 'bulletedList', 'numberedList', 'blockQuote', '|', 'undo', 'redo'],
        heading: {
            options: [
                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                { model: 'heading2', view: 'h2', title: 'Heading', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Subheading', class: 'ck-heading_heading3' },
                { model: 'heading4', view: 'h4', title: 'Minor heading', class: 'ck-heading_heading4' },
            ],
        },
        link: {
            // Matches config/purifier.php's 'cms' profile exactly. A link built with any
            // other scheme (javascript:, data:, etc.) is refused by the editor's own link
            // form before it ever reaches a save request.
            allowedProtocols: ['http', 'https', 'mailto'],
        },
    });

    // The textarea keeps the form's name/id and stays the thing that actually submits; CKEditor
    // replaces its visible rendering but writes back into it on every change, so no other part
    // of a form (validation error display, an Alpine x-model watching the same name) has to
    // know an editor is involved at all.
    editor.model.document.on('change:data', () => {
        textarea.value = editor.getData();
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    });

    instances.set(textarea, editor);

    return editor;
}

export default function initRichTextEditors() {
    const textareas = document.querySelectorAll('textarea[data-richtext]');

    if (textareas.length === 0) {
        return;
    }

    textareas.forEach((textarea) => {
        attach(textarea).catch((error) => {
            // Editors are additive: if CKEditor fails to boot for any reason, the plain
            // textarea underneath is already the real form field and still works.
            console.error('[richtext] failed to attach CKEditor:', error);
        });
    });
}
