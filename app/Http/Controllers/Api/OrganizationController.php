<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Organization::class);

        $organizations = Organization::query()
            ->withCount([
                'users',
                'questions',
                'tests',
                'invitations',
                'attempts as completed_attempts_count' => fn ($query) => $query->where('attempts.status', 'completed'),
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return OrganizationResource::collection($organizations);
    }

    public function store(StoreOrganizationRequest $request)
    {
        $data = $request->validated();

        $organization = new Organization();
        $this->persistOrganization($organization, $data, $request->file('logo'));

        return new OrganizationResource(
            $organization->loadCount(['users', 'questions', 'tests', 'invitations'])
        );
    }

    public function show(Request $request)
    {
        $organization = $request->user()->organization;
        $this->authorize('view', $organization);

        return new OrganizationResource(
            $organization->loadCount(['users', 'questions', 'tests', 'invitations'])
        );
    }

    public function showById(Organization $organization)
    {
        $this->authorize('view', $organization);

        return new OrganizationResource(
            $organization->loadCount([
                'users',
                'questions',
                'tests',
                'invitations',
                'attempts as completed_attempts_count' => fn ($query) => $query->where('attempts.status', 'completed'),
            ])
        );
    }

    public function update(UpdateOrganizationRequest $request)
    {
        $organization = $request->user()->organization;
        $this->authorize('update', $organization);

        $this->persistOrganization($organization, $request->validated(), $request->file('logo'));

        return new OrganizationResource(
            $organization->loadCount(['users', 'questions', 'tests', 'invitations'])
        );
    }

    public function updateById(UpdateOrganizationRequest $request, Organization $organization)
    {
        $this->authorize('update', $organization);

        $this->persistOrganization($organization, $request->validated(), $request->file('logo'));

        return new OrganizationResource(
            $organization->loadCount([
                'users',
                'questions',
                'tests',
                'invitations',
                'attempts as completed_attempts_count' => fn ($query) => $query->where('attempts.status', 'completed'),
            ])
        );
    }

    private function persistOrganization(Organization $organization, array $data, ?UploadedFile $logoFile): void
    {
        if ($logoFile) {
            if ($organization->logo) {
                Storage::disk('public')->delete($organization->logo);
            }

            $data['logo'] = $logoFile->store('organization-logos', 'public');
        }

        if (isset($data['settings'])) {
            unset($data['settings']['default_role_permissions']);

            $data['settings'] = array_replace_recursive(
                [
                    'notifications' => [
                        'email' => true,
                        'sms' => false,
                    ],
                ],
                $organization->settings ?? [],
                $data['settings']
            );
        }

        $organization->fill($data)->save();
    }
}
