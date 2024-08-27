 // a copy to the clipboard function
function ucwpCopyToClipboard(text, messageElement = null) {
	var textArea = document.createElement("textarea");
	textArea.value = text;
	document.body.appendChild(textArea);
	textArea.select();
	document.execCommand("Copy");
	textArea.remove();
	if (messageElement) {
		// if element id is provided, use it to query the element
		if (typeof messageElement === "string") {
			messageElement = document.querySelector(messageElement);
		}
		messageElement.innerHTML = "Copied!";
		setTimeout(function () {
			messageElement.innerHTML = "Click to copy";
		}, 2000);
	}
	else {
		alert("Copied to clipboard");
	}
}

