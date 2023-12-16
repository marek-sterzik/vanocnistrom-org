import $ from "jquery"

$(function() {
    $("div.block").removeClass("visible").addClass("hidden")
    $("div.block>h3").click(function(ev){
        const block = $(this).parent()
        if (block.hasClass("hidden")) {
            block.removeClass("hidden").addClass("visible")
        } else {
            block.removeClass("visible").addClass("hidden")
        }
        ev.preventDefault()
        return false
    })
})
