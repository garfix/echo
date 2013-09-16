function shutEyes()
{
	$('birdEyes').style['display'] = 'block';
	setTimeout(openEyes, 200);
}

function openEyes()
{
	$('birdEyes').style['display'] = 'none';
	setTimeout(shutEyes, 10000);
}

openEyes();