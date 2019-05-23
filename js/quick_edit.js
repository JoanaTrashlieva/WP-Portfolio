function toggleQuickEdit(id) {
    //Checking which button is pressed
    var idNumber = id.getAttribute("data-id-number");
    var tr = id.parentElement.parentElement.nextSibling;
    tr.classList.toggle("quick-edit");
}

function closeQuickEdit(elem){
    var row = elem.parentElement;
    row.parentElement.classList.toggle("quick-edit");
}