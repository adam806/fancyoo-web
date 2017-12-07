$(".jobTitleItem").click(function () {
    var shown = $(this).next().hasClass("itemShow");
    if (!shown) {
        $(".itemDetail").removeClass('itemShow');
        $(this).next().addClass('itemShow');
    } else {
        $(this).next().removeClass('itemShow');
    }
})

$(".techBtn").click(function(){
    chosen(this);
    togglePart(".tech");
});
$(".productionBtn").click(function(){
    chosen(this);
    togglePart(".production");
});
$(".operateBtn").click(function(){
    chosen(this);
    togglePart(".operate");
})
$(".designBtn").click(function(){
    chosen(this);
    togglePart(".desigin");
})
$(".othersBtn").click(function(){
    chosen(this);
    togglePart(".others");
})

function chosen(ele){
    $(".titleBarUl li").removeClass("chosen");
    $(ele).addClass("chosen");
}

function togglePart(part){
    $(".jobDetail").hide();
    $(part).show();
}