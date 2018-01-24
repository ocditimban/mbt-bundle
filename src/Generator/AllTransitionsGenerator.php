<?php

namespace Tienvx\Bundle\MbtBundle\Generator;

use Fhaculty\Graph\Exception\UnderflowException;
use Fhaculty\Graph\Edge\Base as Edge;
use Fhaculty\Graph\Edge\Directed;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Set\Edges;
use Fhaculty\Graph\Vertex;
use Graphp\Algorithms\ConnectedComponents;
use Tienvx\Bundle\MbtBundle\Algorithm\Eulerian;
use Tienvx\Bundle\MbtBundle\Annotation\Generator;

/**
 * @Generator(
 *     name = "all-transitions",
 *     label = "All Transitions"
 * )
 */
class AllTransitionsGenerator extends AbstractGenerator
{
    /**
     * @var bool
     */
    protected $singleComponent = false;

    /**
     * @var Graph
     */
    protected $resultGraph;

    public function init()
    {
        parent::init();

        $components = new ConnectedComponents($this->graph);
        $this->singleComponent = $components->isSingle();
        if ($this->singleComponent) {
            $algorithm = new Eulerian($this->graph);
            $this->resultGraph = $algorithm->getResultGraph();
            $this->currentVertex = $this->resultGraph->getVertex($this->currentVertex->getId());
        }
    }

    public function getNextStep(): ?Directed
    {
        return $this->getRandomUnvisitedEdge($this->currentVertex);
    }

    public function goToNextStep(Directed $currentEdge, bool $callSUT = false)
    {
        $currentEdge->setAttribute('visited', true);
        $this->currentVertex = $currentEdge->getVertexEnd();

        parent::goToNextStep($currentEdge, $callSUT);
    }

    public function meetStopCondition(): bool
    {
        return !$this->singleComponent;
    }

    protected function getRandomUnvisitedEdge(Vertex $vertex): ?Directed
    {
        try {
            /** @var Directed $edge */
            $edge = $vertex->getEdges()->getEdgesMatch(function (Edge $edge) use ($vertex) {
                return $edge->hasVertexStart($vertex) && !$edge->getAttribute('visited');
            })->getEdgeOrder(Edges::ORDER_RANDOM);
            return $edge;
        }
        catch (UnderflowException $e) {
            return null;
        }
    }
}
