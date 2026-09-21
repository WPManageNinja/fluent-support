import { marked } from 'marked';
import DOMPurify from 'dompurify';
import { stripReservedElementPlusClasses } from '@/common/domPurifyGuards';

stripReservedElementPlusClasses(DOMPurify);

const markedOptions = {
    gfm: true,
    breaks: true,
};

export function parseMarkdown(text) {
    if (!text) return '';
    try {
        return DOMPurify.sanitize(marked.parse(text, markedOptions));
    } catch (e) {
        return DOMPurify.sanitize(text.replace(/\n/g, '<br>'));
    }
}
