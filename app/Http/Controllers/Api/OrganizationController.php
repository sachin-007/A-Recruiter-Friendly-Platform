<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($organization->logo) {
                Storage::disk('public')->delete($organization->logo);
            }

            $data['logo'] = $request->file('logo')->store('organization-logos', 'public');
        }

        if (isset($data['settings'])) {
            // Default role permissions are read-only in MVP.
            unset($data['settings']['default_role_permissions']);

            $data['settings'] = array_replace_recursive(
                $organization->settings ?? [],
                $data['settings']
            );
        }

        $organization->update($data);

        return new OrganizationResource($organization);
    }
}
