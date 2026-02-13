<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function show(Request $request)
    {
        $organization = $request->user()->organization;
        $this->authorize('view', $organization);
        return new OrganizationResource($organization);
    }

    public function update(UpdateOrganizationRequest $request)
    {
        $organization = $request->user()->organization;
        $this->authorize('update', $organization);
        $organization->update($request->validated());
        return new OrganizationResource($organization);
    }
}