<?php
namespace App\Http\Controllers;
use App\Models\{LanguageCourse,LanguageLevel,LanguageProgram};
use App\Support\{CenterCode,ExcelExporter};
use Illuminate\Http\{Request,RedirectResponse};
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LanguageCourseController extends Controller {
 public function index(Request $r):View{$q=LanguageCourse::with(['program','level'])->latest();if($r->filled('q')){$s=$r->string('q');$q->where(fn($b)=>$b->where('name','like',"%$s%")->orWhere('textbook','like',"%$s%"));}return view('language.center-courses.index',['items'=>$q->paginate(\App\Support\Pagination::perPage())->withQueryString()]);}
 public function create():View{return $this->form(new LanguageCourse);}
 public function store(Request $r):RedirectResponse{$d=$this->data($r);$d['code']=CenterCode::next('language_courses','KH');LanguageCourse::create($d);return redirect()->route('language-center-courses.index')->with('success','Đã thêm khóa học.');}
 public function edit(LanguageCourse $languageCenterCourse):View{return $this->form($languageCenterCourse);}
 public function update(Request $r,LanguageCourse $languageCenterCourse):RedirectResponse{$languageCenterCourse->update($this->data($r));$languageCenterCourse->classes()->update(['language_program_id'=>$languageCenterCourse->language_program_id,'language_level_id'=>$languageCenterCourse->language_level_id,'expected_sessions'=>$languageCenterCourse->sessions,'default_tuition'=>$languageCenterCourse->tuition]);return redirect()->route('language-center-courses.index')->with('success','Đã cập nhật khóa học và đồng bộ các lớp liên quan.');}
 public function destroy(LanguageCourse $languageCenterCourse):RedirectResponse{$languageCenterCourse->delete();return back()->with('success','Đã xóa khóa học.');}
 public function export(){return ExcelExporter::download('khoa-hoc-trung-tam-'.date('Ymd').'.xlsx',['Mã','Chương trình','Cấp độ','Tên khóa','Giáo trình','Học phí','Thời lượng giờ','Số buổi','Trạng thái'],LanguageCourse::with(['program','level'])->get()->map(fn($i)=>[$i->code,$i->program?->name,$i->level?->name,$i->name,$i->textbook,$i->tuition,$i->duration_hours,$i->sessions,$i->active?'Hoạt động':'Ngừng']));}
 private function form(LanguageCourse $item):View{return view('language.center-courses.form',['item'=>$item,'programs'=>LanguageProgram::where(fn($query)=>$query->where('active',1)->orWhereKey($item->language_program_id))->with('levels')->orderBy('name')->get()]);}
 private function data(Request $r):array{$d=$r->validate(['language_program_id'=>'required|exists:language_programs,id','language_level_id'=>'required|exists:language_levels,id','name'=>'required|max:255','textbook'=>'nullable|max:255','tuition'=>'required|numeric|min:0','duration_hours'=>'required|numeric|min:0','sessions'=>'required|integer|min:0','description'=>'nullable']);$level=LanguageLevel::findOrFail($d['language_level_id']);if($level->language_program_id!==(int)$d['language_program_id'])throw ValidationException::withMessages(['language_level_id'=>'Cấp độ không thuộc chương trình đã chọn.']);$d['active']=$r->boolean('active');return $d;}
}
