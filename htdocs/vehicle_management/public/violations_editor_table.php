<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>قائمة المخالفات مع إداري الاستلام</title>
    <style>
        body {font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin:32px; background:#f9f9f9;}
        table {background:#fff; border-collapse:collapse; width:98%; margin:auto; border-radius:8px; overflow:hidden; box-shadow:0 2px 10px #eee;}
        th, td {padding:9px 10px; text-align:center; border-bottom:1px solid #eee;}
        th {background:#4477aa; color:#fff;}
        tr:hover {background:#eef3f9;}
        .spin {color:#222;margin:40px;text-align:center;font-size:22px;}
    </style>
</head>
<body>
<h2 style="text-align:right;color:#333;margin-bottom:25px;">قائمة المخالفات مع اسم الإداري المسؤول فعليًا وتواريخ الاستلام/الرجوع</h2>
<div id="table-container">
    <div class="spin">جاري التحميل...</div>
</div>
<script>
fetch('../api/v1/violations')
  .then(response => response.json())
  .then(data => {
    if(!data.success) {
        document.getElementById('table-container').innerHTML =
            '<div style="color:#b22;text-align:center;">'+(data.message || 'تعذّر جلب البيانات')+'</div>';
        return;
    }
    const violations = data.data || [];
    let html = `
    <table>
      <tr>
        <th>رقم المخالفة</th>
        <th>كود المركبة</th>
        <th>تاريخ المخالفة</th>
        <th>المبلغ</th>
        <th>الحالة</th>
        <th>تاريخ الاستلام</th>
        <th>تاريخ الرجوع</th>
        <th>رقم الإداري للاستلام</th>
        <th>اسم الإداري للاستلام</th>
      </tr>
    `;
    if (violations.length === 0) {
      html += '<tr><td colspan="9" style="color:#b22;">لا توجد مخالفات</td></tr>';
    } else {
      for(const v of violations) {
        html += `<tr>
          <td>${v.violation_id ? v.violation_id : ''}</td>
          <td>${v.vehicle_code ? v.vehicle_code : ''}</td>
          <td>${v.violation_datetime ? v.violation_datetime : '<span style="color:#b22;">غير موجود</span>'}</td>
          <td>${v.violation_amount ? v.violation_amount : ''}</td>
          <td>${v.violation_status === 'paid' ? 'مدفوعة' : 'غير مدفوعة'}</td>
          <td>${v.pickup_datetime ? v.pickup_datetime : '<span style="color:red;">غير موجود</span>'}</td>
          <td>${v.return_datetime ? v.return_datetime : '<span style="color:gray;">غير موجود</span>'}</td>
          <td>${v.pickup_emp_id ? v.pickup_emp_id : 'غير متوفر'}</td>
          <td>${v.pickup_emp_name ? v.pickup_emp_name : 'غير متوفر'}</td>
        </tr>`;
      }
    }
    html += '</table>';
    document.getElementById('table-container').innerHTML = html;
  })
  .catch(err => {
    document.getElementById('table-container').innerHTML =
        '<div style="color:#b22;text-align:center;">فشل جلب البيانات</div>';
  });
</script>
</body>
</html>