<?php
declare(strict_types=1);

namespace Search\Search\QueryEntry;

/**
 * @category   Zend
 * @package    Zend_Search_Lucene
 * @subpackage Search
 */
class Term extends AbstractQueryEntry
{
    /**
     * Term value
     *
     * @var string
     */
    private $_term;

    /**
     * Field
     *
     * @var string|null
     */
    private $_field;


    /**
     * Fuzzy search query
     *
     * @var boolean
     */
    private $_fuzzyQuery = false;

    /**
     * Similarity
     *
     * @var float
     */
    private $_similarity = 1.;


    /**
     * Object constractor
     *
     * @param string $term
     * @param string $field
     */
    public function __construct($term, $field)
    {
        $this->_term  = $term;
        $this->_field = $field;
    }

    /**
     * Process modifier ('~')
     *
     * @param mixed $parameter
     */
    public function processFuzzyProximityModifier($parameter = null)
    {
        $this->_fuzzyQuery = true;

        if ($parameter !== null) {
            $this->_similarity = $parameter;
        } else {
            $this->_similarity = \Search\Search\Query\Fuzzy::DEFAULT_MIN_SIMILARITY;
        }
    }

    /**
     * Transform entry to a subquery
     *
     * @param string $encoding
     * @return \Search\Search\Query\AbstractQuery
     * @throws \Search\Search\Exception\QueryParserException
     */
    public function getQuery($encoding)
    {
        if ($this->_fuzzyQuery) {
            $query = new \Search\Search\Query\Preprocessing\Fuzzy($this->_term,
                                                                             $encoding,
                                                                             ($this->_field !== null)?
                                                                                  iconv($encoding, 'UTF-8', $this->_field) :
                                                                                  null,
                                                                             $this->_similarity
                                                                             );
            $query->setBoost($this->_boost);
            return $query;
        }


        $query = new \Search\Search\Query\Preprocessing\Term($this->_term,
                                                                        $encoding,
                                                                        ($this->_field !== null)?
                                                                              iconv($encoding, 'UTF-8', $this->_field) :
                                                                              null
                                                                        );
        $query->setBoost($this->_boost);
        return $query;
    }
}
