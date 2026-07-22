<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public const THEMES = ['blue','navy','indigo','purple','pink','rose','red','orange','yellow','lime','green','teal','cyan','brown','slate'];

    public function edit(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);
        return view('settings.theme', [
            'theme'=>SystemSetting::valueOf('theme_color','blue'),
            'softwareName'=>SystemSetting::valueOf('software_name','E-SKY CENTER'),
            'logoPath'=>SystemSetting::valueOf('logo_path'),
            'loadingStyle'=>SystemSetting::valueOf('loading_style','center'),
            'bankEnabled'=>SystemSetting::valueOf('bank_enabled','0') === '1',
            'bankBin'=>SystemSetting::valueOf('bank_bin','970428'),
            'bankName'=>SystemSetting::valueOf('bank_name','Nam A Bank'),
            'bankAccountNumber'=>SystemSetting::valueOf('bank_account_number',''),
            'bankAccountName'=>SystemSetting::valueOf('bank_account_name',''),
            'bankBranch'=>SystemSetting::valueOf('bank_branch',''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data=$request->validate([
            'software_name'=>['required','string','max:80'],'theme_color'=>['required',Rule::in(self::THEMES)],
            'logo'=>['nullable','image','mimes:png,jpg,jpeg,webp','max:2048'],'remove_logo'=>['nullable','boolean'],
            'loading_style'=>['required',Rule::in(['center','top'])],'bank_enabled'=>['nullable','boolean'],
            'bank_bin'=>['nullable','required_if:bank_enabled,1','digits:6'],
            'bank_name'=>['nullable','required_if:bank_enabled,1','string','max:100'],
            'bank_account_number'=>['nullable','required_if:bank_enabled,1','regex:/^[0-9]{4,30}$/'],
            'bank_account_name'=>['nullable','required_if:bank_enabled,1','string','max:150'],
            'bank_branch'=>['nullable','string','max:150'],
        ],[
            'software_name.required'=>'Vui lòng nhập tên phần mềm.','logo.image'=>'Logo phải là tệp hình ảnh.',
            'logo.mimes'=>'Logo chỉ hỗ trợ PNG, JPG hoặc WEBP.','logo.max'=>'Logo không được lớn hơn 2 MB.',
            'bank_bin.required_if'=>'Vui lòng nhập mã BIN ngân hàng.','bank_bin.digits'=>'Mã BIN ngân hàng phải gồm 6 chữ số.',
            'bank_account_number.required_if'=>'Vui lòng nhập số tài khoản.','bank_account_number.regex'=>'Số tài khoản chỉ được chứa chữ số.',
            'bank_account_name.required_if'=>'Vui lòng nhập tên chủ tài khoản.','bank_name.required_if'=>'Vui lòng nhập tên ngân hàng.',
        ]);

        $logoPath=SystemSetting::valueOf('logo_path');
        if($request->boolean('remove_logo')||$request->hasFile('logo')){$this->deleteLogo($logoPath);$logoPath=null;}
        if($request->hasFile('logo')){
            $directory=public_path('uploads/branding');File::ensureDirectoryExists($directory);
            $file=$request->file('logo');$filename='logo-'.now()->format('YmdHis').'.'.$file->extension();
            $file->move($directory,$filename);$logoPath='uploads/branding/'.$filename;
        }

        $settings=[
            'software_name'=>trim($data['software_name']),'theme_color'=>$data['theme_color'],'logo_path'=>$logoPath,
            'loading_style'=>$data['loading_style'],'bank_enabled'=>$request->boolean('bank_enabled')?'1':'0',
            'bank_bin'=>trim($data['bank_bin']??''),'bank_name'=>trim($data['bank_name']??''),
            'bank_account_number'=>trim($data['bank_account_number']??''),'bank_account_name'=>mb_strtoupper(trim($data['bank_account_name']??'')),
            'bank_branch'=>trim($data['bank_branch']??''),
        ];
        foreach($settings as $key=>$value) SystemSetting::updateOrCreate(['key'=>$key],['value'=>$value]);
        ActivityLogger::log('settings','update_branding','Cập nhật cấu hình phần mềm và tài khoản ngân hàng');
        return back()->with('success','Đã cập nhật cấu hình phần mềm và tài khoản ngân hàng.');
    }

    private function deleteLogo(?string $logoPath): void
    {
        if(!$logoPath||!str_starts_with($logoPath,'uploads/branding/')) return;
        $fullPath=public_path($logoPath);if(File::isFile($fullPath)) File::delete($fullPath);
    }
}
