<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subject }}</title>
    <style>
        @page {
            margin: 90px 70px 80px 70px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            line-height: 1.5;
        }

        .letterhead {
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 10px;
            margin-bottom: 24px;
        }

        .letterhead__facility {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .letterhead__tagline {
            font-size: 10px;
            color: #555;
            margin-top: 2px;
        }

        .meta-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 3px 0;
            vertical-align: top;
            font-size: 12px;
        }

        .meta-table td.label {
            width: 90px;
            font-weight: bold;
            color: #333;
        }

        .subject-line {
            font-size: 13px;
            font-weight: bold;
            margin: 18px 0 20px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #ccc;
        }

        .body-text {
            font-size: 12px;
            text-align: justify;
            white-space: pre-wrap;
        }

        .signature-block {
            margin-top: 60px;
            font-size: 12px;
        }

        .signature-line {
            margin-top: 40px;
            width: 220px;
            border-top: 1px solid #1a1a1a;
            padding-top: 4px;
            font-size: 10px;
            color: #555;
        }

        .footer-note {
            position: fixed;
            bottom: -60px;
            left: 0;
            right: 0;
            font-size: 9px;
            color: #888;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="letterhead">
        <div class="letterhead__facility">Inmate Information Management System</div>
        <div class="letterhead__tagline">Official Correspondence</div>
    </div>

    <table class="meta-table">
        <tr>
            <td class="label">Date:</td>
            <td>{{ $date }}</td>
        </tr>
        @if(!empty($recipient))
        <tr>
            <td class="label">To:</td>
            <td>{{ $recipient }}</td>
        </tr>
        @endif
    </table>

    <div class="subject-line">
        Subject: {{ $subject }}
    </div>

    <div class="body-text">{{ trim($body) }}</div>

    <div class="signature-block">
        <div class="signature-line">Authorized Signature</div>
    </div>

    <div class="footer-note">
        Generated via Atom AI Assistant &middot; {{ $date }}
    </div>
</body>
</html>
