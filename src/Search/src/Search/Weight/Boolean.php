<?php
declare(strict_types=1);

namespace Search\Search\Weight;

use Search\SearchIndexInterface;
use Search\Search\Query;

class Boolean extends AbstractWeight
{
    /**
     * IndexReader.
     *
     * @var \Search\SearchIndexInterface
     */
    private $_reader;

    /**
     * The query that this concerns.
     *
     * @var \Search\Search\Query\AbstractQuery
     */
    private $_query;

    /**
     * Queries weights
     * Array of Zend_Search_Lucene_Search_Weight
     *
     * @var array
     */
    private $_weights;


    /**
     * Zend_Search_Lucene_Search_Weight_Boolean constructor
     * query - the query that this concerns.
     * reader - index reader
     *
     * @param \Search\Search\Query\AbstractQuery $query
     * @param \Search\SearchIndexInterface    $reader
     */
    public function __construct(Query\AbstractQuery $query, SearchIndexInterface    $reader)
    {
        $this->_query   = $query;
        $this->_reader  = $reader;
        $this->_weights = array();

        $signs = $query->getSigns();

        foreach ($query->getSubqueries() as $num => $subquery) {
            if ($signs === null || $signs[$num] === null || $signs[$num]) {
                $this->_weights[$num] = $subquery->createWeight($reader);
            }
        }
    }


    /**
     * The weight for this query
     * Standard Weight::$_value is not used for boolean queries
     *
     * @return float
     */
    public function getValue()
    {
        return $this->_query->getBoost();
    }


    /**
     * The sum of squared weights of contained query clauses.
     *
     * @return float
     */
    public function sumOfSquaredWeights()
    {
        $sum = 0;
        foreach ($this->_weights as $weight) {
            // sum sub weights
            $sum += $weight->sumOfSquaredWeights();
        }

        // boost each sub-weight
        $sum *= $this->_query->getBoost() * $this->_query->getBoost();

        // check for empty query (like '-something -another')
        if ($sum == 0) {
            $sum = 1.0;
        }
        return $sum;
    }


    /**
     * Assigns the query normalization factor to this.
     *
     * @param float $queryNorm
     */
    public function normalize($queryNorm)
    {
        // incorporate boost
        $queryNorm *= $this->_query->getBoost();

        foreach ($this->_weights as $weight) {
            $weight->normalize($queryNorm);
        }
    }
}
