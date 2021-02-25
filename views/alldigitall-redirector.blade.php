<form action="{!! $success_url !!}" method="post" id="success_form"></form>
<form action="{!! $cancel_url !!}" method="post" id="cancel_form"></form>

<p style="text-align: center;">درگاه پرداخت تست آل دیجیتال</p>
<table style="margin-left: auto; margin-right: auto;">
<tbody>
<tr>
<td style="text-align: center;">
<div id="success"><h2><span style="background-color: #008000; color: #ffffff;">&nbsp; پرداخت موفق&nbsp; &nbsp; </span></h2></div>
</td>
<td style="text-align: center;"><span style="color: #ffffff;">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></td>
<td style="text-align: center;">
<div id="cancel"><h2><span style="background-color: #ff6600; color: #ffffff;">&nbsp; لغو پرداخت &nbsp; &nbsp;</span></h2></div>
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
