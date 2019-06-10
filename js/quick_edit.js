//open quick edit from row actions
function toggleQuickEdit(id) {
    var idNumber = id.getAttribute("data-id-number");
    var tr = id.parentElement.parentElement.nextSibling;
    tr.classList.toggle("quick-edit");
}

//discard button from the quick edit menu
function closeQuickEdit(elem){
    var row = elem.parentElement;
    row.parentElement.classList.toggle("quick-edit");
}

