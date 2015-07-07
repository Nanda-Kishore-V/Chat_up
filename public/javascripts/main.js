window.onload = init;
function init()
{
	document.getElementById("options1").onclick = displaySelfOptions;
	if(document.getElementById("options2"))
	{
		document.getElementById("options2").onclick = displayUserOptions;
	}
	document.getElementById("display_profile").onclick = displayProfile;
	document.getElementById("back_from_profile").onclick = function(){document.getElementById("profile_container").style.visibility = "hidden";};
	document.getElementById("back_from_contacts").onclick = function(){document.getElementById("contacts_container").style.visibility = "hidden";};
	document.getElementById("contacts1").onclick = displayContacts;
}
function displaySelfOptions(eventObject)
{
	var menu = document.getElementById("menu1");
	if(menu.style.visibility === "visible")
	{
		menu.style.visibility = "hidden";
		return false;
	}
	else
	{
		menu.style.visibility = "visible";
		return false;
	}
}
function displayUserOptions()
{
	var menu = document.getElementById("menu2");
	if(menu.style.visibility === "visible")
	{
		menu.style.visibility = "hidden";
		document.getElementById("messages").style.width = "100%";
		document.getElementById("send_message").style.width = "100%";
		return false;
	}
	else
	{
		menu.style.visibility = "visible";
		document.getElementById("messages").style.width = "60%";
		document.getElementById("send_message").style.width = "60%";
		return false;
	}
}
function displayProfile()
{
	document.getElementById("profile_container").style.visibility = "visible";
	document.getElementById("options1").click();
}
function displayContacts()
{
	document.getElementById("contacts_container").style.visibility = "visible";
}