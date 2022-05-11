<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:x="urn:schemas-microsoft-com:office:excel"
xmlns="http://www.w3.org/TR/REC-html40">

<html>
<head>
	<meta http-equiv="Content-type" content="text/html;charset=utf-8" />
</head>
<body>
<div>

<table x:num border="1" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse">
	<tr>
		{foreach from=$headings item=fname}
			<td>{$fname}</td>
		{/foreach}
	</tr>
	{foreach from=$rows item=row}
		<tr>
			{foreach from=$row item=fval}
				<td>{$fval}</td>
			{/foreach}
		</tr>
	{/foreach}
</table>
</div>
</body>
</html>


