<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Công việc mới từ E-SKY CENTER</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;color:#172033;font-family:Arial,Helvetica,sans-serif">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9">
    <tr>
        <td align="center" style="padding:32px 16px">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;overflow:hidden;border-radius:18px;background:#ffffff;box-shadow:0 12px 35px rgba(15,23,42,.10)">
                <tr>
                    <td style="padding:24px 32px;background:linear-gradient(135deg,#0b2559,#2563eb)">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                                <td width="72" valign="middle">
                                    <span style="display:inline-block;padding:7px;border-radius:12px;background:#ffffff">
                                        <img src="{{ $logoUrl }}" width="58" alt="E-SKY CENTER" style="display:block;max-width:58px;height:auto;border:0">
                                    </span>
                                </td>
                                <td valign="middle" style="padding-left:14px;color:#ffffff">
                                    <div style="font-size:20px;font-weight:800;letter-spacing:.03em">E-SKY CENTER</div>
                                    <div style="margin-top:4px;font-size:12px;opacity:.82">HỆ THỐNG QUẢN LÝ TRUNG TÂM</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:34px 32px">
                        <div style="margin-bottom:8px;color:#2563eb;font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase">Thông báo công việc</div>
                        <h1 style="margin:0 0 18px;color:#0f172a;font-size:25px;line-height:1.35">Bạn có công việc mới</h1>
                        <p style="margin:0 0 22px;color:#475569;font-size:15px;line-height:1.7">
                            Xin chào <strong>{{ $recipient->name }}</strong>,<br>
                            <strong>{{ $assigner->name }}</strong> vừa giao cho bạn một công việc mới.
                        </p>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:26px;border:1px solid #dbe5f1;border-radius:14px;background:#f8fafc">
                            <tr><td style="padding:20px 22px">
                                <div style="margin-bottom:15px;color:#0f172a;font-size:18px;font-weight:800">{{ $task->title }}</div>
                                <div style="margin:8px 0;color:#64748b;font-size:14px"><strong style="color:#334155">Ưu tiên:</strong> {{ $priority }}</div>
                                <div style="margin:8px 0;color:#64748b;font-size:14px"><strong style="color:#334155">Hạn hoàn thành:</strong> {{ $schedule }}</div>
                                @if(filled($task->description))
                                    <div style="margin:14px 0 0;padding-top:14px;border-top:1px solid #e2e8f0;color:#475569;font-size:14px;line-height:1.65;white-space:pre-line">{{ $task->description }}</div>
                                @endif
                            </td></tr>
                        </table>

                        <table role="presentation" cellspacing="0" cellpadding="0">
                            <tr><td style="border-radius:10px;background:#2563eb">
                                <a href="{{ $taskUrl }}" style="display:inline-block;padding:14px 24px;color:#ffffff;font-size:14px;font-weight:800;text-decoration:none">Mở công việc</a>
                            </td></tr>
                        </table>
                        <p style="margin:24px 0 0;color:#64748b;font-size:13px;line-height:1.6">Vui lòng đăng nhập E-SKY để xác nhận nhận việc và cập nhật tiến độ.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:18px 32px;border-top:1px solid #e2e8f0;color:#94a3b8;background:#f8fafc;font-size:12px;text-align:center">
                        Email được gửi tự động từ E-SKY CENTER. Vui lòng không trả lời email này.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
