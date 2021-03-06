<?php

namespace AppBundle\Repository;

/**
 * ReviewRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ReviewRepository extends \Doctrine\ORM\EntityRepository
{
	public function getReviewByLocation($location)
	{
        $queryBuilder = $this->createQueryBuilder('r')
        					 ->where('r.location = :location')
	    			         ->orderBy('r.dateIns', 'DESC')
    			             ->setParameter('location', $location);
    	return $queryBuilder;
	}
}
