<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Support\ActivityLogger;
use App\Support\OpenAiSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public const THEMES = ['white','gradient-ocean','gradient-aurora','gradient-sunset','gradient-galaxy','gradient-berry','gradient-forest','gradient-candy','gradient-fire','pastel-blue','pastel-sky','pastel-mint','pastel-green','pastel-lavender','pastel-purple','pastel-pink','pastel-peach','pastel-yellow','pastel-gray','blue','navy','azure','sky','cyan','teal','mint','emerald','green','lime','olive','yellow','amber','orange','coral','red','rose','pink','fuchsia','purple','violet','indigo','brown','slate','graphite'];

    public function section(Request $request, string $section): View
    {
        abort_unless($request->user()->allowed('software_settings','view'), 403);
        $openAi = app(OpenAiSettings::class);
        return view('settings.section', [
            'section' => $section,
            'theme' => SystemSetting::valueOf('theme_color', 'blue'),
            'softwareName' => SystemSetting::valueOf('software_name', 'E-SKY CENTER'),
            'logoPath' => $this->existingLogoPath(SystemSetting::valueOf('logo_path')),
            'loadingStyle' => SystemSetting::valueOf('loading_style', 'center'),
            'visualEffect' => SystemSetting::valueOf('visual_effect', 'standard'),
            'footerText' => SystemSetting::valueOf('footer_text', '© 2026 E-sky center v1.0.0 | Phát triển bởi Đặng Việt Duy'),
            'defaultPerPage' => (int) SystemSetting::valueOf('default_per_page', 10),
            'bankEnabled' => SystemSetting::valueOf('bank_enabled', '0') === '1',
            'bankBin' => SystemSetting::valueOf('bank_bin', '970428'),
            'bankName' => SystemSetting::valueOf('bank_name', 'Nam A Bank'),
            'bankAccountNumber' => SystemSetting::valueOf('bank_account_number', ''),
            'bankAccountName' => SystemSetting::valueOf('bank_account_name', ''),
            'bankBranch' => SystemSetting::valueOf('bank_branch', ''),
            'openAiEnabled' => $openAi->enabled(),
            'openAiKeyConfigured' => $openAi->hasApiKey(),
            'openAiKeyStored' => $openAi->hasStoredApiKey(),
            'openAiModel' => $openAi->model(),
            'openAiTimeout' => $openAi->timeout(),
        ]);
    }

    public function updateSection(Request $request, string $section): RedirectResponse
    {
        abort_unless($request->user()->allowed('software_settings','update'), 403);
        if ($section === 'general') {
            $data = $request->validate([
                'software_name' => 'required|string|max:80', 'footer_text' => 'required|string|max:200',
                'default_per_page' => 'required|integer|in:10,20,30,50,100',
                'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048', 'remove_logo' => 'nullable|boolean',
            ]);
            $logoPath = SystemSetting::valueOf('logo_path');
            if ($request->boolean('remove_logo') || $request->hasFile('logo')) { $this->deleteLogo($logoPath); $logoPath = null; }
            if ($request->hasFile('logo')) {
                $directory = public_path('uploads/branding'); File::ensureDirectoryExists($directory);
                $file = $request->file('logo'); $filename = 'logo-'.now()->format('YmdHis').'.'.$file->extension();
                $file->move($directory, $filename); $logoPath = 'uploads/branding/'.$filename;
            }
            $this->saveSettings(['software_name'=>trim($data['software_name']), 'footer_text'=>trim($data['footer_text']),
                'default_per_page'=>(string)$data['default_per_page'], 'logo_path'=>$logoPath]);
        } elseif ($section === 'appearance') {
            $data = $request->validate(['theme_color'=>['required',Rule::in(self::THEMES)], 'loading_style'=>['required',Rule::in(['center','top'])], 'visual_effect'=>['required',Rule::in(['standard','soft','glass','glow'])]]);
            $this->saveSettings($data);
        } elseif ($section === 'ai') {
            $data = $request->validate([
                'openai_enabled' => 'nullable|boolean',
                'openai_api_key' => 'nullable|string|max:500',
                'remove_openai_api_key' => 'nullable|boolean',
                'openai_report_model' => ['required', Rule::in(OpenAiSettings::MODELS)],
                'openai_timeout' => 'required|integer|in:15,30,45,60,90',
            ]);

            $settings = [
                'openai_enabled' => $request->boolean('openai_enabled') ? '1' : '0',
                'openai_report_model' => $data['openai_report_model'],
                'openai_timeout' => (string) $data['openai_timeout'],
            ];
            if ($request->boolean('remove_openai_api_key')) {
                $settings['openai_api_key_encrypted'] = '';
            } elseif (trim((string) ($data['openai_api_key'] ?? '')) !== '') {
                $settings['openai_api_key_encrypted'] = Crypt::encryptString(trim($data['openai_api_key']));
            }
            $this->saveSettings($settings);
        } elseif ($section === 'payment') {
            $data = $request->validate([
                'bank_enabled'=>'nullable|boolean', 'bank_bin'=>'nullable|required_if:bank_enabled,1|digits:6',
                'bank_name'=>'nullable|required_if:bank_enabled,1|string|max:100',
                'bank_account_number'=>'nullable|required_if:bank_enabled,1|regex:/^[0-9]{4,30}$/',
                'bank_account_name'=>'nullable|required_if:bank_enabled,1|string|max:150', 'bank_branch'=>'nullable|string|max:150',
            ]);
            $this->saveSettings(['bank_enabled'=>$request->boolean('bank_enabled')?'1':'0', 'bank_bin'=>trim($data['bank_bin']??''),
                'bank_name'=>trim($data['bank_name']??''), 'bank_account_number'=>trim($data['bank_account_number']??''),
                'bank_account_name'=>mb_strtoupper(trim($data['bank_account_name']??'')), 'bank_branch'=>trim($data['bank_branch']??'')]);
        }
        ActivityLogger::log('settings', 'update_'.$section, 'Cập nhật cấu hình '.$section);
        return back()->with('success', 'Đã lưu cấu hình.');
    }

    private function saveSettings(array $settings): void
    {
        foreach ($settings as $key => $value) SystemSetting::updateOrCreate(['key'=>$key], ['value'=>$value]);
    }

    public function edit(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);
        return view('settings.theme', [
            'theme'=>SystemSetting::valueOf('theme_color','blue'),
            'softwareName'=>SystemSetting::valueOf('software_name','E-SKY CENTER'),
            'logoPath'=>$this->existingLogoPath(SystemSetting::valueOf('logo_path')),
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

    private function existingLogoPath(?string $logoPath): ?string
    {
        return $logoPath
            && str_starts_with($logoPath, 'uploads/branding/')
            && File::isFile(public_path($logoPath))
                ? $logoPath
                : null;
    }
}
