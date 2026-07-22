<?php
namespace App\Http\Controllers;
use App\Models\{LanguageProgram,LanguageLevel}; use Illuminate\Http\{Request,RedirectResponse}; use Illuminate\Validation\Rule; use Illuminate\View\View;
class LanguageProgramController extends Controller {
 public function index(Request $r):View{$q=LanguageProgram::with('levels')->withCount(['classes as filtered_classes_count'=>fn($b)=>$r->filled('class_status')?$b->where('status',$r->class_status):$b]);if($r->filled('class_status'))$q->whereHas('classes',fn($b)=>$b->where('status',$r->class_status));if($r->filled('q')){$s=$r->string('q');$q->where(fn($b)=>$b->where('name','like',"%$s%")->orWhere('code','like',"%$s%"));}return view('language.programs.index',['items'=>$q->orderBy('name')->paginate(\App\Support\Pagination::perPage())->withQueryString()]);}
 public function create():View{return view('language.programs.form',['item'=>new LanguageProgram]);}
 public function store(Request $r):RedirectResponse{LanguageProgram::create($this->data($r));return redirect()->route('language-programs.index')->with('success','Đã tạo chương trình.');}
 public function edit(LanguageProgram $languageProgram):View{return view('language.programs.form',['item'=>$languageProgram]);}
 public function update(Request $r,LanguageProgram $languageProgram):RedirectResponse{$languageProgram->update($this->data($r,$languageProgram));return redirect()->route('language-programs.index')->with('success','Đã cập nhật chương trình.');}
 public function destroy(LanguageProgram $languageProgram):RedirectResponse{$languageProgram->delete();return back()->with('success','Đã xóa chương trình.');}
 public function storeLevel(Request $r,LanguageProgram $languageProgram):RedirectResponse{$languageProgram->levels()->create($this->levelData($r));return back()->with('success','Đã thêm cấp độ.');}
 public function destroyLevel(LanguageProgram $languageProgram,LanguageLevel $level):RedirectResponse{abort_unless($level->language_program_id===$languageProgram->id,404);$level->delete();return back()->with('success','Đã xóa cấp độ.');}
 private function data(Request $r,?LanguageProgram $m=null):array{$d=$r->validate(['code'=>['required','max:30',Rule::unique('language_programs')->ignore($m)],'name'=>'required|max:255','audience'=>'nullable|max:255','description'=>'nullable']);$d['active']=$r->boolean('active');return $d;}
 private function levelData(Request $r):array{return $r->validate(['code'=>'required|max:30|unique:language_levels,code','name'=>'required|max:255','expected_sessions'=>'required|integer|min:0','expected_hours'=>'required|numeric|min:0','default_tuition'=>'required|numeric|min:0','passing_score'=>'nullable|numeric|min:0','description'=>'nullable']);}
}
