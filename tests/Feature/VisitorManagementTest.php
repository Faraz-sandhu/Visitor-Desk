<?php
namespace Tests\Feature;
use App\Models\Company; use App\Models\Employee; use App\Models\User; use App\Models\Visit; use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class VisitorManagementTest extends TestCase { use RefreshDatabase;
 public function test_guests_are_redirected_to_login(): void { $this->get('/')->assertRedirect('/login'); }
 public function test_receptionist_can_check_in_and_check_out_visitor(): void { $user=User::factory()->create(['role'=>'admin']); $company=Company::create(['name'=>'Acme','is_active'=>true]); $employee=Employee::create(['company_id'=>$company->id,'name'=>'Jane','is_active'=>true]); $this->actingAs($user)->post('/visits',['employee_id'=>$employee->id,'visitor_name'=>'Ali Khan','id_card_number'=>'35202-1234567-1','purpose'=>'Project meeting'])->assertRedirect(); $visit=Visit::first(); $this->assertNull($visit->check_out_at); $this->actingAs($user)->patch("/visits/{$visit->id}/checkout")->assertRedirect(); $this->assertNotNull($visit->fresh()->check_out_at); }
 public function test_visit_requires_identity_host_and_purpose(): void { $this->actingAs(User::factory()->create(['role'=>'admin']))->post('/visits',[])->assertSessionHasErrors(['employee_id','visitor_name','id_card_number','purpose']); }

 private function visitorData(): array {
  $company=Company::create(['name'=>'Test office','is_active'=>true]);
  $employee=Employee::create(['company_id'=>$company->id,'name'=>'Host','is_active'=>true]);
  return ['employee_id'=>$employee->id,'visitor_name'=>'Guest','id_card_number'=>'ID123','purpose'=>'Meeting'];
 }
 public function test_check_in_uses_today_and_selected_time_and_ignores_submitted_date_and_company(): void {
  $this->travelTo(\Illuminate\Support\Carbon::parse('2026-09-21 10:15:00'));
  $this->actingAs(User::factory()->create(['role'=>'admin']))->post('/visits',$this->visitorData()+['check_in_time'=>'09:30','check_in_at'=>'2020-01-01 01:00:00','visitor_company'=>'Unneeded'])->assertSessionHasNoErrors();
  $visit=Visit::first();
  $this->assertSame('2026-09-21 09:30',$visit->check_in_at->format('Y-m-d H:i'));
  $this->assertNull($visit->visitor_company);
 }
 public function test_edit_preserves_original_date_and_rejects_time_after_checkout(): void {
  $user=User::factory()->create(['role'=>'admin']);$data=$this->visitorData();
  $visit=Visit::create($data+['created_by'=>$user->id,'check_in_at'=>'2026-09-18 09:00:00','check_out_at'=>'2026-09-18 11:00:00']);
  $this->actingAs($user)->put('/visits/'.$visit->id,$data+['check_in_time'=>'10:00'])->assertSessionHasNoErrors();
  $this->assertSame('2026-09-18 10:00',$visit->fresh()->check_in_at->format('Y-m-d H:i'));
  $this->put('/visits/'.$visit->id,$data+['check_in_time'=>'12:00'])->assertSessionHasErrors('check_in_time');
 }
 public function test_invalid_time_is_rejected(): void {
  $this->actingAs(User::factory()->create(['role'=>'admin']))->post('/visits',$this->visitorData()+['check_in_time'=>'28:90'])->assertSessionHasErrors('check_in_time');
 }
 public function test_all_screens_render_and_check_in_has_only_time(): void {
  $this->actingAs(User::factory()->create(['role'=>'admin']));
  foreach (['/','/visits','/visits/create','/employees','/employees/create','/companies','/companies/create'] as $url) $this->get($url)->assertOk();
  $this->get('/visits/create')->assertSee('name="check_in_time"',false)->assertDontSee('name="visitor_company"',false)->assertDontSee('datetime-local',false);
 }
}
