<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SearchResultHighlighter
{
    /**
     * The string to search in.
     *
     * @var string
     */
    protected $haystack;

    /**
     * The number of characters.
     *
     * @var int
     */
    protected $limit;

    /**
     * The string to search for.
     *
     * @var string
     */
    protected $needle;

    /**
     * The processed string.
     *
     * @var string
     */
    protected $result;

    /**
     * HTML tag to highlight the needle(s)
     *
     * @var string
     */
    protected $highlightTag = 'mark';

    /**
     * @param $needle
     * @param null $haystack
     * @param null $limit
     */
    public function __construct($needle = null, $haystack = null, $limit = 200)
    {
        $this->setHaystack($haystack);
        $this->setNeedle($needle);
        $this->setLimit($limit);
    }

    /**
     * @param $haystack
     * @return $this
     */
    public function setHaystack($haystack)
    {
        $this->haystack = $haystack;

        return $this;
    }

    /**
     * @param $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param $needle
     * @return $this
     */
    public function setNeedle($needle)
    {
        $this->needle = $needle;

        return $this;
    }

    /**
     * @return string
     */
    public function get()
    {
        $this->result = $this->haystack;

        $this->sliceHaystack();
        $this->limitHaystack();
        $this->highlightNeedle();

        return $this->result;
    }

    /**
     * Limit the number of characters of the haystack.
     *
     * @return null
     */
    protected function limitHaystack()
    {
        $this->result = Str::limit($this->result, $this->limit);
    }

    /**
     * Find the sentence with the first occurrence of the needle and
     * remove everything before this sentence.
     *
     * @return null
     */
    protected function sliceHaystack()
    {
        // Check if the needle is present
        $firstOccurence = stripos($this->result, $this->needle);

        if ($firstOccurence === false) {
            return;
        }

        // Get a collection of sentences and find the first sentence
        // with the needle present
        $sentences = $this->getSentences();

        $sentencesWithNeedle = $this->getSentencesWithNeedle()->toArray();

        reset($sentencesWithNeedle);
        $firstSentenceKey = key($sentencesWithNeedle);

        // Use the first sentence as the offset
        $sentences = $sentences->slice($firstSentenceKey);

        // Implode the remaining sentences
        $this->result = $sentences->implode(" ");
    }

    /**
     * Wrap the needle around HTML tags.
     *
     * @return null
     */
    protected function highlightNeedle()
    {
        $this->result = preg_replace(
            '#' . preg_quote($this->needle) . '#i',
            '<' . $this->highlightTag . '>\\0</' . $this->highlightTag . '>',
            $this->result
        );
    }

    /**
     * Split the haystack into sentences
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getSentences()
    {
        // http://stackoverflow.com/a/5844564
        $regex = '/# Split sentences on whitespace between them.
    (?<=                # Begin positive lookbehind.
      [.!?]             # Either an end of sentence punct,
    | [.!?][\'"]        # or end of sentence punct and quote.
    )                   # End positive lookbehind.
    (?<!                # Begin negative lookbehind.
      Mr\.              # Skip either "Mr."
    | Mrs\.             # or "Mrs.",
    | Ms\.              # or "Ms.",
    | Jr\.              # or "Jr.",
    | Dr\.              # or "Dr.",
    | Prof\.            # or "Prof.",
    | Sr\.              # or "Sr.",
    | T\.V\.A\.         # or "T.V.A.",
                        # or... (you get the idea).
    )                   # End negative lookbehind.
    \s+                 # Split on whitespace between sentences.
    /ix';

        $sentences = preg_split($regex, $this->result, -1, PREG_SPLIT_NO_EMPTY);

        return new Collection($sentences);
    }

    /**
     * Return the sentences where the needle is present.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getSentencesWithNeedle()
    {
        return $this->getSentences()->filter(function ($sentence) {
            return stripos($sentence, $this->needle) !== false;
        });
    }
}
