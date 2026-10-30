<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use Workbench\App\Models\User;

beforeEach(function (): void {
    [$this->acme, $this->scimToken] = scimOrg();
    $this->ada = (string) scim('POST', '/Users', oktaUser())->json('id');
    $this->grace = (string) scim('POST', '/Users', oktaUser('grace@acme.test', '00u2'))->json('id');
});

it('creates a group as a team with members', function (): void {
    $group = scim('POST', '/Groups', ['schemas' => ['urn:ietf:params:scim:schemas:core:2.0:Group'], 'displayName' => 'Engineering', 'members' => [['value' => $this->ada]]])
        ->assertCreated()
        ->assertJsonPath('displayName', 'Engineering')
        ->assertJsonPath('members.0.value', $this->ada)
        ->json();

    $team = TeamModel::query()->sole();

    expect($team->name)->toBe('Engineering')
        ->and($team->hasMember(User::query()->where('email', 'ada@acme.test')->sole()))->toBeTrue();

    scim('GET', '/Users/'.$this->ada)->assertJsonPath('groups.0.value', $group['id']);
});

it('adds, removes and replaces members with PATCH', function (): void {
    $id = (string) scim('POST', '/Groups', ['displayName' => 'Engineering'])->json('id');

    scim('PATCH', '/Groups/'.$id, patchOps([['op' => 'add', 'path' => 'members', 'value' => [['value' => $this->ada], ['value' => $this->grace]]]]))
        ->assertOk()
        ->assertJsonCount(2, 'members');

    scim('PATCH', '/Groups/'.$id, patchOps([['op' => 'remove', 'path' => 'members[value eq "'.$this->ada.'"]']]))
        ->assertOk()
        ->assertJsonCount(1, 'members')
        ->assertJsonPath('members.0.value', $this->grace);

    scim('PATCH', '/Groups/'.$id, patchOps([['op' => 'replace', 'path' => 'displayName', 'value' => 'Platform']]))
        ->assertOk()
        ->assertJsonPath('displayName', 'Platform');

    scim('GET', '/Groups?filter='.urlencode('members.value eq "'.$this->grace.'"'))->assertJsonPath('totalResults', 1);
    scim('GET', '/Groups?filter='.urlencode('displayName eq "platform"'))->assertJsonPath('totalResults', 1);
});

it('refuses members from outside the organization', function (): void {
    scim('POST', '/Groups', ['displayName' => 'Eng', 'members' => [['value' => '01JAAAAAAAAAAAAAAAAAAAAAAA']]])->assertStatus(400);
});

it('deletes a group and its team', function (): void {
    $id = (string) scim('POST', '/Groups', ['displayName' => 'Engineering'])->json('id');

    scim('DELETE', '/Groups/'.$id)->assertNoContent();

    expect(TeamModel::query()->count())->toBe(0);
});
