<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use RefactorCircus\Guardian\Domains\Scim\Models\ScimUserModel;

beforeEach(function (): void {
    [$this->acme, $this->scimToken] = scimOrg();
});

it('rejects unsupported filters', function (string $filter): void {
    scim('GET', '/Users?filter='.urlencode($filter))->assertStatus(400)->assertJsonPath('scimType', 'invalidFilter');
})->with([
    'unknown attribute' => 'title eq "x"',
    'unknown operator' => 'userName gt "x"',
    'or' => 'userName eq "a" or userName eq "b"',
    'garbage' => 'userName eq "x"; DROP TABLE users',
    'unterminated' => 'userName eq "x',
]);

it('pages with startIndex and count', function (): void {
    foreach (['a', 'b', 'c'] as $name) {
        scim('POST', '/Users', oktaUser($name.'@acme.test', $name))->assertCreated();
    }

    scim('GET', '/Users?startIndex=2&count=1')
        ->assertOk()
        ->assertJsonPath('totalResults', 3)
        ->assertJsonPath('startIndex', 2)
        ->assertJsonPath('itemsPerPage', 1)
        ->assertJsonPath('Resources.0.userName', 'b@acme.test');
});

it('honours ETags', function (): void {
    $created = scim('POST', '/Users', oktaUser());
    $id = (string) $created->json('id');
    $etag = (string) $created->headers->get('ETag');

    expect($etag)->toStartWith('W/"')->toBe($created->json('meta.version'));

    test()->call('GET', '/scim/v2/acme/Users/'.$id, [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$this->scimToken, 'HTTP_IF_NONE_MATCH' => $etag])->assertStatus(304);

    scim('PATCH', '/Users/'.$id, patchOps([['op' => 'replace', 'path' => 'displayName', 'value' => 'X']]), headers: ['If-Match' => 'W/"stale"'])
        ->assertStatus(412);

    scim('PATCH', '/Users/'.$id, patchOps([['op' => 'replace', 'path' => 'displayName', 'value' => 'X']]), headers: ['If-Match' => $etag])
        ->assertOk();
});

it('runs bulk requests with bulkId references', function (): void {
    $response = scim('POST', '/Bulk', [
        'schemas' => ['urn:ietf:params:scim:api:messages:2.0:BulkRequest'],
        'Operations' => [
            ['method' => 'POST', 'path' => '/Users', 'bulkId' => 'ada', 'data' => oktaUser()],
            ['method' => 'POST', 'path' => '/Groups', 'bulkId' => 'eng', 'data' => ['displayName' => 'Engineering', 'members' => [['value' => 'bulkId:ada']]]],
            ['method' => 'PATCH', 'path' => '/Users/bulkId:ada', 'data' => patchOps([['op' => 'replace', 'path' => 'displayName', 'value' => 'Countess']])],
        ],
    ])->assertOk();

    expect(collect($response->json('Operations'))->pluck('status')->all())->toBe(['201', '201', '200']);

    scim('GET', '/Groups')->assertJsonPath('Resources.0.members.0.value', ScimUserModel::query()->sole()->id);
});

it('stops bulk work after failOnErrors', function (): void {
    $response = scim('POST', '/Bulk', [
        'failOnErrors' => 1,
        'Operations' => [
            ['method' => 'POST', 'path' => '/Users', 'data' => oktaUser('eve@gmail.test')],
            ['method' => 'POST', 'path' => '/Users', 'data' => oktaUser()],
        ],
    ])->assertOk();

    expect($response->json('Operations'))->toHaveCount(1)
        ->and($response->json('Operations.0.status'))->toBe('400')
        ->and(ScimUserModel::query()->count())->toBe(0);
});

it('limits bulk size', function (): void {
    config()->set('guardian.scim.bulk.max_operations', 1);

    scim('POST', '/Bulk', ['Operations' => [
        ['method' => 'POST', 'path' => '/Users', 'data' => oktaUser()],
        ['method' => 'POST', 'path' => '/Users', 'data' => oktaUser('b@acme.test', 'b')],
    ]])->assertStatus(413)->assertJsonPath('scimType', 'tooMany');
});

it('describes itself', function (): void {
    scim('GET', '/ServiceProviderConfig')
        ->assertOk()
        ->assertJsonPath('patch.supported', true)
        ->assertJsonPath('bulk.supported', true)
        ->assertJsonPath('etag.supported', true)
        ->assertJsonPath('changePassword.supported', false)
        ->assertJsonPath('authenticationSchemes.0.type', 'oauthbearertoken');

    scim('GET', '/ResourceTypes')->assertOk()->assertJsonPath('totalResults', 2)->assertJsonPath('Resources.0.endpoint', '/Users');
    scim('GET', '/Schemas')->assertOk()->assertJsonPath('Resources.0.id', 'urn:ietf:params:scim:schemas:core:2.0:User');
});

it('matches eq filters exactly, even with LIKE wildcards in the value', function (): void {
    scim('POST', '/Users', oktaUser('a_b@acme.test', 'ext-1'))->assertCreated();
    scim('POST', '/Users', oktaUser('axb@acme.test', 'ext-2'))->assertCreated();

    scim('GET', '/Users?filter='.urlencode('userName eq "A_B@acme.test"'))
        ->assertOk()
        ->assertJsonPath('totalResults', 1)
        ->assertJsonPath('Resources.0.userName', 'a_b@acme.test');

    scim('GET', '/Users?filter='.urlencode('userName eq "AXB@ACME.TEST"'))
        ->assertOk()
        ->assertJsonPath('totalResults', 1)
        ->assertJsonPath('Resources.0.userName', 'axb@acme.test');
});
