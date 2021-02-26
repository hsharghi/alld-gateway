<form action="{!! $success_url !!}" method="post" id="success_form"></form>
<form action="{!! $cancel_url !!}" method="post" id="cancel_form"></form>


<h2 style="text-align: center;">درگاه پرداخت تست آل دیجیتال</h2>
<table style="margin-left: auto; margin-right: auto; width: 343.71875px;">
<tbody>
<tr>
<td style="text-align: center; width: 131px;">{!! $order_id !!}</td>
<td style="text-align: center; width: 54px;">&nbsp;</td>
<td style="text-align: center; width: 137.71875px;">
<h4><span style="color: #000000;">شماره سفارش</span></h4>
</td>
</tr>
<tr>
<td style="text-align: center; width: 131px;">{!! $amount !!}</td>
<td style="text-align: center; width: 54px;">&nbsp;</td>
<td style="text-align: center; width: 137.71875px;">
<h4>مبلغ قابل پرداخت</h4>
</td>
</tr>
<tr>
<td style="text-align: center; width: 131px;">&nbsp;</td>
<td style="text-align: center; width: 54px;">&nbsp;</td>
<td style="text-align: center; width: 137.71875px;">&nbsp;</td>
</tr>
<tr>
<td style="text-align: center; width: 131px;">
<div id="success">
<h2><span style="background-color: #008000; color: #ffffff;">&nbsp; پرداخت موفق&nbsp; &nbsp; </span></h2>
</div>
</td>
<td style="text-align: center; width: 54px;"><span style="color: #ffffff;">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></td>
<td style="text-align: center; width: 137.71875px;">
<div id="cancel">
<h2><span style="background-color: #ff6600; color: #ffffff;">&nbsp; لغو پرداخت &nbsp; &nbsp;</span></h2>
</div>
</td>
</tr>
</tbody>
</table>
<script type="text/javascript">
var success_button = document.getElementById('success');
var cancel_button = document.getElementById('cancel');

success_button.style.cursor = 'pointer';
success_button.onclick = function() {
	var f=document.getElementById('success_form');
	f.submit();
};

cancel_button.style.cursor = 'pointer';
cancel_button.onclick = function() {
	var f=document.getElementById('cancel_form');
	f.submit();
};

</script>
