<?php

namespace Wexample\SymfonyDocument\Service;

use LogicException;
use Wexample\SymfonyDocument\Entity\Document;
use Wexample\SymfonyDocument\Entity\DocumentSegment;
use Wexample\SymfonyDocument\Repository\DocumentSegmentRepository;

/**
 * Cuts a document into the passages of each voice, the way transcripts write them: a
 * line opening on the speaker's name and a colon.
 *
 *     JOURNALISTE : Allez-vous baisser les impôts ?
 *     Jean Dupont : Nous allons y travailler.
 *
 * Only for a text known to be written that way: in free prose, a line opening on a
 * capitalised word and a colon (« Rien : … ») would be read as a speaker.
 */
class DocumentSegmentationService
{
    /**
     * A name at the start of a line, then a colon. Each word of the name opens on a
     * capital, so that a sentence holding a colon (« Il a dit : non ») is not taken
     * for a speaker. Five words at most.
     */
    private const string SPEAKER_LINE_PATTERN = '/^[ \t]*(\p{Lu}[\p{L}\'’.-]*(?:[ \t]+\p{Lu}[\p{L}\'’.-]*){0,4})[ \t]*:[ \t]*/mu';

    public function __construct(
        private readonly DocumentSegmentRepository $segmentRepository,
    ) {
    }

    /**
     * Segments are created on the document, not flushed: they are persisted with it.
     * Text before the first speaker, if any, becomes a segment with no speaker.
     *
     * @return list<DocumentSegment> the segments created, in order; none when no line
     *                               names a speaker
     *
     * @throws LogicException when the document is already cut
     */
    public function segmentBySpeakerLines(Document $document): array
    {
        if (! $document->getSegments()->isEmpty()) {
            throw new LogicException(sprintf('Document "%s" is already cut into segments.', $document));
        }

        $content = $document->getContent();
        preg_match_all(self::SPEAKER_LINE_PATTERN, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        if ([] === $matches) {
            return [];
        }

        // Each turn runs from the end of its speaker's label to the start of the next
        // label. Offsets come from the regex in bytes, and are counted in characters.
        $turns = [];

        if ('' !== trim(substr($content, 0, $matches[0][0][1]))) {
            $turns[] = [null, 0, $matches[0][0][1]];
        }

        foreach ($matches as $index => $match) {
            $turns[] = [
                $match[1][0],
                $match[0][1] + strlen($match[0][0]),
                $matches[$index + 1][0][1] ?? strlen($content),
            ];
        }

        $segments = [];

        foreach ($turns as [$speaker, $startByte, $endByte]) {
            $text = substr($content, $startByte, $endByte - $startByte);
            $trimmed = rtrim($text);

            if ('' === $trimmed) {
                continue;
            }

            $startOffset = mb_strlen(substr($content, 0, $startByte));

            $segments[] = $this->segmentRepository->createNewDocumentSegment(
                $document,
                $startOffset,
                $startOffset + mb_strlen($trimmed),
                $speaker,
            );
        }

        return $segments;
    }
}
