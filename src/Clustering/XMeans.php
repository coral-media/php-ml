<?php

namespace Phpml\Clustering;

use Phpml\Exception\InvalidArgumentException;

class XMeans extends KMeans
{
    private int $maxClusters; // Maximum number of clusters

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        int $minClusters = 2,
        int $maxClusters = 4,
        int $initialization = KMeans::INIT_KMEANS_PLUS_PLUS
    ) {
        parent::__construct($minClusters, $initialization); // Call KMeans constructor
        $this->maxClusters = $maxClusters;
    }

    /**
     * Overrides the cluster method to add X-Means refinement.
     *
     * @param array $samples The dataset to cluster
     *
     * @return array The refined clusters
     *
     * @throws InvalidArgumentException
     */
    public function cluster(array $samples): array
    {
        // Initial clustering using KMeans
        $clusters = parent::cluster($samples);

        // Refine clusters using X-Means logic
        return $this->refineClusters($clusters);
    }

    /**
     * Refines clusters using the X-Means splitting logic.
     *
     * @param array $clusters The initial clusters
     *
     * @return array Refined clusters
     *
     * @throws InvalidArgumentException
     */
    private function refineClusters(array $clusters): array
    {
        $newClusters = [];

        foreach ($clusters as $cluster) {
            $bicBefore = $this->calculateBIC([$cluster]);

            // Split the cluster into two using KMeans
            $splitClusters = $this->clusterSubset($cluster, 2);
            $bicAfter = $this->calculateBIC($splitClusters);

            if ($bicAfter > $bicBefore) {
                $newClusters = array_merge($newClusters, $splitClusters);
            } else {
                $newClusters[] = $cluster;
            }
        }

        // Ensure we don’t exceed the max cluster count
        return count($newClusters) > $this->maxClusters ? $clusters : $newClusters;
    }

    /**
     * Helper method to cluster a subset of data points.
     *
     * @param array $subset      The subset of data points
     * @param int   $numClusters Number of clusters for this subset
     *
     * @return array The clusters from the subset
     *
     * @throws InvalidArgumentException
     */
    private function clusterSubset(array $subset, int $numClusters): array
    {
        $kMeans = new KMeans($numClusters);

        return $kMeans->cluster($subset);
    }

    /**
     * Calculates BIC for given clusters.
     *
     * @param array $clusters The clusters to evaluate
     *
     * @return float BIC score
     */
    private function calculateBIC(array $clusters): float
    {
        $n = 0; // Total number of points
        $totalLogLikelihood = 0;

        $dimensionality = count(array_values($clusters)[0]); // Assumes non-empty clusters
        $numClusters = count($clusters);

        foreach ($clusters as $cluster) {
            $nCluster = count($cluster);
            $n += $nCluster;

            // Compute cluster variance
            $variance = $this->calculateClusterVariance($cluster);

            // Compute log-likelihood for this cluster
            $logLikelihood = $nCluster * log($nCluster) -
                $nCluster * log($n) -
                ($nCluster * $dimensionality / 2) * log(2 * M_PI * $variance) -
                ($nCluster - 1) / 2;
            $totalLogLikelihood += $logLikelihood;
        }

        // Compute the number of parameters (k)
        $k = $numClusters * ($dimensionality + 1);

        // Final BIC value
        return $totalLogLikelihood - ($k / 2) * log($n);
    }

    /**
     * Calculates variance for a single cluster.
     *
     * @param array $cluster The points in the cluster
     *
     * @return float Cluster variance
     */
    private function calculateClusterVariance(array $cluster): float
    {
        $n = count($cluster);
        if ($n <= 1) {
            return 1e-10; // To prevent division by zero or very small variance
        }

        $dimensionality = count(array_values($cluster)[0]);
        $means = array_fill(0, $dimensionality, 0);

        // Calculate means for each dimension
        foreach ($cluster as $point) {
            for ($i = 0; $i < $dimensionality; ++$i) {
                $means[$i] += $point[$i];
            }
        }
        for ($i = 0; $i < $dimensionality; ++$i) {
            $means[$i] /= $n;
        }

        // Calculate variance
        $variance = 0;
        foreach ($cluster as $point) {
            for ($i = 0; $i < $dimensionality; ++$i) {
                $variance += pow($point[$i] - $means[$i], 2);
            }
        }

        return $variance / ($n - 1);
    }
}
