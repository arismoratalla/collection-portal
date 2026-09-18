<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Services\ProjectStatusService;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function home(ProjectStatusService $status): View
    {
        return view('portal.home', [
            'report' => $status->report(),
        ]);
    }

    public function collections(ProjectStatusService $status): View
    {
        return view('portal.collections.index', [
            'collections' => $status->collections(),
        ]);
    }

    public function show(Collection $collection, ProjectStatusService $status): View
    {
        return view('portal.collections.show', [
            'summary' => $status->collectionDetail($collection),
        ]);
    }

    public function workflow(ProjectStatusService $status): View
    {
        return view('portal.workflow', [
            'workflowSteps' => $status->workflowSteps(),
            'nextRecommendedStep' => $status->nextRecommendedStep(),
        ]);
    }

    public function status(ProjectStatusService $status): View
    {
        return view('portal.status', [
            'report' => $status->report(),
        ]);
    }
}
