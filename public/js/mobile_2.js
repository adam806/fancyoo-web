var onOpen = false;
$(".slideBtn").click(function () {
    if (!onOpen) {
        $(".slideDownContainer").addClass("onOpen");
        onOpen = true;

        $(".line1").removeClass('line1AnimationUp').addClass('line1AnimationDown');
        $(".line2").removeClass('line2Animation2').addClass('line2Animation1');
        $(".line3").removeClass('line3AnimationDown').addClass('line3AnimationUp');

    } else {
        $(".slideDownContainer").removeClass("onOpen");
        onOpen = false;
        $(".line1").removeClass('line1AnimationDown').addClass('line1AnimationUp');
        $(".line2").removeClass('line2Animation1').addClass('line2Animation2');
        $(".line3").removeClass('line3AnimationUp').addClass('line3AnimationDown');
    }
});