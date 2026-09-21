<?php
namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InlineCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_be_created_inline_selected_for_employee_and_deleted_when_unused(): void
    {
        $this->actingAs(User::factory()->create(['role'=>'admin']));
        $response = $this->postJson('/companies', ['name'=>'  New workplace  ','is_active'=>true])->assertCreated()->assertJsonPath('company.name','New workplace');
        $id = $response->json('company.id');
        $this->post('/employees', ['name'=>'New host','company_id'=>$id,'is_active'=>true])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('employees', ['name'=>'New host','company_id'=>$id]);
        $this->deleteJson('/companies/'.$id)->assertStatus(409);
        $this->assertDatabaseHas('companies',['id'=>$id]);
        $this->delete('/employees/'.Employee::first()->id)->assertRedirect();
        $this->deleteJson('/companies/'.$id)->assertOk();
        $this->assertDatabaseMissing('companies',['id'=>$id]);
    }

    public function test_duplicate_blank_and_oversized_names_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role'=>'admin']));
        Company::create(['name'=>'Acme','is_active'=>true]);
        foreach ([' acme ', '', str_repeat('a',151)] as $name) {
            $this->postJson('/companies',['name'=>$name,'is_active'=>true])->assertUnprocessable()->assertJsonValidationErrors('name');
        }
        $this->assertDatabaseCount('companies',1);
    }

    public function test_directory_headers_and_company_picker_render(): void
    {
        $this->actingAs(User::factory()->create(['role'=>'admin']));
        foreach (['/employees','/employees/create','/companies','/companies/create','/visits/create'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('New check-in');
        }
        $this->get('/visits')->assertSee('New check-in')->assertSee('Visit history');
        $this->get('/employees/create')->assertSee('data-company-search',false)->assertSee('data-add-company',false);
    }

    public function test_inline_mutations_require_authentication(): void
    {
        $company=Company::create(['name'=>'Protected']);
        $this->postJson('/companies',['name'=>'Blocked'])->assertUnauthorized();
        $this->deleteJson('/companies/'.$company->id)->assertUnauthorized();
        $this->assertDatabaseHas('companies',['id'=>$company->id]);
    }
}
