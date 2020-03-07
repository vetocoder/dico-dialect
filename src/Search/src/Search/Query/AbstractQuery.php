<?php
declare(strict_types=1);

namespace Search\Search\Query;

use Search\SearchIndexInterface;
use Search\Document;
use Search\Search\Highlighter\DefaultHighlighter;
use Search\Search\Highlighter\HighlighterInterface as Highlighter;

/**
 * @category   Zend
 * @package    Zend_Search_Lucene
 * @subpackage Search
 */
abstract class AbstractQuery
{
    /**
     * query boost factor
     *
     * @var float
     */
    private $_boost = 1;

    /**
     * AbstractQuery weight
     *
     * @var \Search\Search\Weight\AbstractWeight
     */
    protected $_weight = null;

    /**
     * Gets the boost for this clause.  Documents matching
     * this clause will (in addition to the normal weightings) have their score
     * multiplied by boost.   The boost is 1.0 by default.
     *
     * @return float
     */
    public function getBoost()
    {
        return $this->_boost;
    }

    /**
     * Sets the boost for this query clause to $boost.
     *
     * @param float $boost
     */
    public function setBoost($boost)
    {
        $this->_boost = $boost;
    }

    /**
     * Score specified document
     *
     * @param integer $docId
     * @param \Search\SearchIndexInterface $reader
     * @return float
     */
    abstract public function score($docId, SearchIndexInterface $reader);

    /**
     * Get document ids likely matching the query
     *
     * It's an array with document ids as keys (performance considerations)
     *
     * @return array
     */
    abstract public function matchedDocs();

    /**
     * Execute query in context of index reader
     * It also initializes necessary internal structures
     *
     * AbstractQuery specific implementation
     *
     * @param \Search\SearchIndexInterface $reader
     * @param \Search\Index\DocsFilter|null $docsFilter
     */
    abstract public function execute(SearchIndexInterface $reader, $docsFilter = null);

    /**
     * Constructs an appropriate Weight implementation for this query.
     *
     * @param \Search\SearchIndexInterface $reader
     * @return \Search\Search\Weight\AbstractWeight
     */
    abstract public function createWeight(SearchIndexInterface $reader);

    /**
     * Constructs an initializes a Weight for a _top-level_query_.
     *
     * @param \Search\SearchIndexInterface $reader
     */
    protected function _initWeight(SearchIndexInterface $reader)
    {
        // Check, that it's a top-level query and query weight is not initialized yet.
        if ($this->_weight !== null) {
            return $this->_weight;
        }

        $this->createWeight($reader);
        $sum = $this->_weight->sumOfSquaredWeights();
        $queryNorm = $reader->getSimilarity()->queryNorm($sum);
        $this->_weight->normalize($queryNorm);
    }

    /**
     * Re-write query into primitive queries in the context of specified index
     *
     * @param \Search\SearchIndexInterface $index
     * @return \Search\Search\Query\AbstractQuery
     */
    abstract public function rewrite(SearchIndexInterface $index);

    /**
     * Optimize query in the context of specified index
     *
     * @param \Search\SearchIndexInterface $index
     * @return \Search\Search\Query\AbstractQuery
     */
    abstract public function optimize(SearchIndexInterface $index);

    /**
     * Reset query, so it can be reused within other queries or
     * with other indeces
     */
    public function reset()
    {
        $this->_weight = null;
    }


    /**
     * Print a query
     *
     * @return string
     */
    abstract public function __toString();

    /**
     * Return query terms
     *
     * @return array
     */
    abstract public function getQueryTerms();

    /**
     * AbstractQuery specific matches highlighting
     *
     * @param Highlighter $highlighter  Highlighter object (also contains doc for highlighting)
     */
    abstract protected function _highlightMatches(Highlighter $highlighter);

    /**
     * Highlight matches in $inputHTML
     *
     * @param string $inputHTML
     * @param string  $defaultEncoding   HTML encoding, is used if it's not specified using Content-type HTTP-EQUIV meta tag.
     * @param Highlighter|null $highlighter
     * @return string
     */
    public function highlightMatches($inputHTML, $defaultEncoding = '', $highlighter = null)
    {
        if ($highlighter === null) {
            $highlighter = new DefaultHighlighter();
        }

        $doc = Document\HTML::loadHTML($inputHTML, false, $defaultEncoding);
        $highlighter->setDocument($doc);

        $this->_highlightMatches($highlighter);

        return $doc->getHTML();
    }

    /**
     * Highlight matches in $inputHTMLFragment and return it (without HTML header and body tag)
     *
     * @param string $inputHTMLFragment
     * @param string  $encoding   Input HTML string encoding
     * @param Highlighter|null $highlighter
     * @return string
     */
    public function htmlFragmentHighlightMatches($inputHTMLFragment, $encoding = 'UTF-8', $highlighter = null)
    {
        if ($highlighter === null) {
            $highlighter = new DefaultHighlighter();
        }

        $inputHTML = '<html><head><META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=UTF-8"/></head><body>'
                   . iconv($encoding, 'UTF-8//IGNORE', $inputHTMLFragment) . '</body></html>';

        $doc = Document\HTML::loadHTML($inputHTML);
        $highlighter->setDocument($doc);

        $this->_highlightMatches($highlighter);

        return $doc->getHTMLBody();
    }
}
