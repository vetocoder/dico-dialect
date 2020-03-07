<?php
declare(strict_types=1);

namespace Search\Search\QueryEntry;

/**
 * @category   Zend
 * @package    Zend_Search_Lucene
 * @subpackage Search
 */
class Phrase extends AbstractQueryEntry
{
    /**
     * Phrase value
     *
     * @var string
     */
    private $_phrase;

    /**
     * Field
     *
     * @var string|null
     */
    private $_field;


    /**
     * Proximity phrase query
     *
     * @var boolean
     */
    private $_proximityQuery = false;

    /**
     * Words distance, used for proximiti queries
     *
     * @var integer
     */
    private $_wordsDistance = 0;


    /**
     * Object constractor
     *
     * @param string $phrase
     * @param string $field
     */
    public function __construct($phrase, $field)
    {
        $this->_phrase = $phrase;
        $this->_field  = $field;
    }

    /**
     * Process modifier ('~')
     *
     * @param mixed $parameter
     */
    public function processFuzzyProximityModifier($parameter = null)
    {
        $this->_proximityQuery = true;

        if ($parameter !== null) {
            $this->_wordsDistance = $parameter;
        }
    }

    /**
     * Transform entry to a subquery
     *
     * @param string $encoding
     * @throws \Search\Search\Exception\QueryParserException
     * @return \Search\Search\Query\AbstractQuery
     */
    public function getQuery($encoding)
    {
        $query = new \Search\Search\Query\Preprocessing\Phrase($this->_phrase,
                                                                          $encoding,
                                                                          ($this->_field !== null)?
                                                                              iconv($encoding, 'UTF-8', $this->_field) :
                                                                              null);

        if ($this->_proximityQuery) {
            $query->setSlop($this->_wordsDistance);
        }

        $query->setBoost($this->_boost);

        return $query;
    }
}
