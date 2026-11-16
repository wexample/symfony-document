<?php

namespace Wexample\SymfonyDocument\Enum;

/**
 * What a document was before it became text: how it was delivered, which says how its
 * text was obtained — written, or transcribed from speech — and so how far it can be
 * trusted to the word.
 */
enum DocumentKind: string
{
    /** Delivered by one voice, to an audience. */
    case Speech = 'speech';

    /** Written to be read: a press article, a column, a release. */
    case Article = 'article';

    /** Questions and answers, two voices or more. */
    case Interview = 'interview';

    /** Several voices facing each other. */
    case Debate = 'debate';

    /** Recorded to be heard, then transcribed. */
    case Podcast = 'podcast';

    case Other = 'other';

    /**
     * Whether the document is expected to hold several voices, and so to be cut into
     * segments, each with its speaker.
     */
    public function isMultiVoice(): bool
    {
        return match ($this) {
            self::Interview, self::Debate, self::Podcast => true,
            default => false,
        };
    }
}
