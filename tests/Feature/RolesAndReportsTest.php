<?php
namespace Tests\Feature;
use App\Models\{User,Visit,Company,Employee};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash,Storage};
use Tests\TestCase;

class RolesAndReportsTest extends TestCase {
 use RefreshDatabase;
 private function visit(array $attributes=[]): Visit {
  $user=User::factory()->create(['role'=>'admin']);
  $company=Company::firstOrCreate(['name'=>'Report office'],['is_active'=>true]);
  $employee=Employee::firstOrCreate(['name'=>'Report host','company_id'=>$company->id],['is_active'=>true]);
  return Visit::create(array_merge(['visitor_name'=>'Report guest','id_card_number'=>'ID001','employee_id'=>$employee->id,'purpose'=>'Meeting','created_by'=>$user->id,'check_in_at'=>'2026-09-21 12:00:00','check_out_at'=>'2026-09-21 16:00:00'],$attributes));
 }
 public function test_desk_permissions_are_enforced_on_server_and_in_ui(): void {
  $visit=$this->visit();$desk=User::factory()->create(['role'=>'desk']);$this->actingAs($desk);
  foreach(['/reports','/reports/download','/users','/users/create','/visits/'.$visit->id.'/edit','/employees/'.$visit->employee_id.'/edit','/companies'] as $url)$this->get($url)->assertForbidden();
  $this->post('/users',[])->assertForbidden();$this->put('/users/'.$desk->id,['role'=>'admin'])->assertForbidden();
  $this->delete('/visits/'.$visit->id)->assertForbidden();$this->put('/visits/'.$visit->id,[])->assertForbidden();$this->delete('/employees/'.$visit->employee_id)->assertForbidden();
  $this->get('/visits/'.$visit->id)->assertOk()->assertDontSee('Edit visit')->assertDontSee('Delete visit record')->assertDontSee('Users &amp; roles');
  $this->get('/employees/create')->assertOk()->assertDontSee('data-delete-company=',false);
  $this->post('/employees',['name'=>'Desk host','company_id'=>$visit->employee->company_id,'is_active'=>true])->assertSessionHasNoErrors();
  $this->post('/visits',['visitor_name'=>'Desk guest','id_card_number'=>'ID2','purpose'=>'Meeting','employee_id'=>$visit->employee_id])->assertSessionHasNoErrors();
  $new=Visit::where('visitor_name','Desk guest')->firstOrFail();$this->patch('/visits/'.$new->id.'/checkout')->assertRedirect();$this->assertNotNull($new->fresh()->check_out_at);
 }
 public function test_duration_handles_hours_minutes_overnight_and_open_visits(): void {
  $visit=$this->visit();$this->assertSame('4 hours',$visit->durationLabel());
  $visit->check_in_at='2026-09-21 13:00:00';$visit->check_out_at='2026-09-21 14:20:00';$this->assertSame('1 hour 20 minutes',$visit->durationLabel());
  $visit->check_in_at='2026-09-21 23:30:00';$visit->check_out_at='2026-09-22 01:00:00';$this->assertSame('1 hour 30 minutes',$visit->durationLabel());
  $visit->check_out_at=null;$this->assertSame('1 hour 30 minutes',$visit->durationLabel(\Illuminate\Support\Carbon::parse('2026-09-22 01:00:00')));
 }
 public function test_reports_filter_daily_monthly_and_export_durations_and_safe_csv(): void {
  $this->visit(['visitor_name'=>'=SUM(1,2)']);$this->visit(['visitor_name'=>'Previous day','check_in_at'=>'2026-09-20 13:00:00','check_out_at'=>'2026-09-20 14:20:00']);$this->visit(['visitor_name'=>'Next month','check_in_at'=>'2026-10-01 00:00:00','check_out_at'=>null]);
  $this->actingAs(User::factory()->create(['role'=>'admin']));
  $this->get('/reports?period=daily&date=2026-09-21')->assertOk()->assertSee('4 hours')->assertDontSee('Previous day')->assertDontSee('Next month');
  $this->get('/reports?period=monthly&month=2026-09')->assertOk()->assertSee('1 hour 20 minutes')->assertSee('Previous day')->assertDontSee('Next month');
  $csv=$this->get('/reports/download?period=monthly&month=2026-09')->assertOk()->assertDownload('visitors-monthly-2026-09.csv')->streamedContent();
  $this->assertStringContainsString("'=SUM(1,2)",$csv);$this->assertStringContainsString('4 hours',$csv);$this->assertStringContainsString('1 hour 20 minutes',$csv);$this->assertStringNotContainsString('Next month',$csv);
  $this->get('/reports?period=monthly&month=invalid')->assertSessionHasErrors('month');
 }
 public function test_admin_creates_users_with_hashed_passwords_and_cannot_demote_last_admin(): void {
  $admin=User::factory()->create(['role'=>'admin']);$this->actingAs($admin);
  $this->get('/users')->assertOk();$this->get('/users/create')->assertOk();$this->get('/users/'.$admin->id.'/edit')->assertOk();
  $this->post('/users',['name'=>'Reception','email'=>'desk@example.test','role'=>'desk','password'=>'desk-password','password_confirmation'=>'desk-password'])->assertSessionHasNoErrors();
  $this->assertTrue(Hash::check('desk-password',User::where('email','desk@example.test')->firstOrFail()->password));
  $this->put('/users/'.$admin->id,['name'=>$admin->name,'email'=>$admin->email,'role'=>'desk'])->assertSessionHasErrors('role');$this->assertTrue($admin->fresh()->isAdmin());
 }
 public function test_photo_is_available_to_signed_in_users_without_storage_link(): void {
  Storage::fake('public');Storage::disk('public')->put('visitor-photos/test.png',base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6SAAAAABJRU5ErkJggg=='));
  $visit=$this->visit(['photo_path'=>'visitor-photos/test.png']);
  $this->get('/visits/'.$visit->id.'/photo')->assertRedirect('/login');
  $this->actingAs(User::factory()->create(['role'=>'desk']));$this->get('/visits/'.$visit->id.'/photo')->assertOk()->assertHeader('Content-Type','image/png');
  $this->get('/visits/'.$visit->id)->assertOk()->assertSee('data-view-photo',false);
  Storage::disk('public')->delete('visitor-photos/test.png');$this->get('/visits/'.$visit->id.'/photo')->assertNotFound();
 }
}
