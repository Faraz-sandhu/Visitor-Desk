<?php
namespace App\Http\Controllers;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
class ReportController extends Controller {
 public function index(Request $request) {
  [$start,$end,$period,$date,$month]=$this->range($request);
  $query=Visit::with('employee.company','receptionist')->where('check_in_at','>=',$start)->where('check_in_at','<',$end);
  $total=(clone $query)->count();$completed=(clone $query)->whereNotNull('check_out_at')->count();
  $visits=$query->orderByDesc('check_in_at')->paginate(25)->withQueryString();
  $asOf=now();
  return view('reports.index',compact('visits','total','completed','period','date','month','start','end','asOf'));
 }
 public function download(Request $request) {
  [$start,$end,$period]=$this->range($request);$asOf=now();
  return response()->streamDownload(function() use($start,$end,$asOf){
   $out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");
   fputcsv($out,['Visit ID','Visitor','ID / CNIC / Passport','Phone','Email','Host','Host company','Purpose','Check-in (Asia/Karachi)','Check-out (Asia/Karachi)','Time in office','Duration minutes','Status','Recorded by','Notes','Report generated at']);
   foreach(Visit::with('employee.company','receptionist')->where('check_in_at','>=',$start)->where('check_in_at','<',$end)->orderBy('id')->lazyById(500) as $visit){
    $row=[$visit->id,$visit->visitor_name,$visit->id_card_number,$visit->visitor_phone,$visit->visitor_email,$visit->employee->name,$visit->employee->company->name,$visit->purpose,$visit->check_in_at->format('Y-m-d H:i:s'),$visit->check_out_at?->format('Y-m-d H:i:s'),$visit->durationLabel($asOf),$visit->durationMinutes($asOf),$visit->check_out_at?'Checked out':'Still inside - elapsed so far',$visit->receptionist->name,$visit->notes,$asOf->format('Y-m-d H:i:s')];
    fputcsv($out,array_map(fn($v)=>preg_match('/^[\s]*[=+@-]/u',(string)$v)?"'".$v:$v,$row));
   }
   fclose($out);
  },'visitors-'.$period.'-'.$start->format($period==='daily'?'Y-m-d':'Y-m').'.csv',['Content-Type'=>'text/csv; charset=UTF-8','Cache-Control'=>'private, no-store']);
 }
 private function range(Request $request): array {
  $data=$request->validate(['period'=>['nullable','in:daily,monthly'],'date'=>['nullable','date_format:Y-m-d'],'month'=>['nullable','date_format:Y-m']]);
  $period=$data['period']??'daily';$date=$data['date']??now()->format('Y-m-d');$month=$data['month']??now()->format('Y-m');
  $start=Carbon::parse($period==='monthly'?$month.'-01':$date)->startOfDay();
  $end=$period==='monthly'?$start->copy()->addMonth():$start->copy()->addDay();
  return [$start,$end,$period,$date,$month];
 }
}
