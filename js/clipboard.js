import $ from "jquery"


function fallbackCopyTextToClipboard(text) {
  var textArea = document.createElement("textarea");
  textArea.value = text;
  
  // Avoid scrolling to bottom
  textArea.style.top = "0";
  textArea.style.left = "0";
  textArea.style.position = "fixed";

  document.body.appendChild(textArea);
  textArea.focus();
  textArea.select();

  try {
    var successful = document.execCommand('copy');
    var msg = successful ? 'successful' : 'unsuccessful';
    console.log('Fallback: Copying text command was ' + msg);
  } catch (err) {
    console.error('Fallback: Oops, unable to copy', err);
  }

  document.body.removeChild(textArea);
}

function copyToClipboard(text) {
  if (!navigator.clipboard) {
    fallbackCopyTextToClipboard(text);
    return;
  }
  navigator.clipboard.writeText(text).then(function() {
    console.log('Async: Copying to clipboard was successful!');
  }, function(err) {
    console.error('Async: Could not copy text: ', err);
  });
}


var currentChecked = null
var currentCheckedTimeout = null


$(function() {
 $("button.copy-to-clipboard").bind("click", function() {
    const el = $(this)
    const text = el.next("pre").text()
    if (text !== undefined) {
        copyToClipboard(text)
        if (currentChecked !== null) {
            currentChecked.removeClass("copied")
            currentChecked = null
        }
        if (currentCheckedTimeout !== null) {
            clearTimeout(currentCheckedTimeout)
            currentCheckedTimeout = null
        }
        el.addClass("copied")
        currentChecked = el
        currentCheckedTimeout = setTimeout(() => {
            if (currentChecked !== null) {
                currentChecked.removeClass("copied")
            }
            currentChecked = null
            currentCheckedTimeout = null
        }, 5000)
    }
 })
})
