var carousel = true;
$(".item").click(function () {
    if (carousel) {
        carouselPouse()
    } else {
        carouselBegin()
    }
})

function carouselPouse() {
    $('#myCarousel').carousel('pause');
    carousel = false;
}

function carouselBegin() {
    $('#myCarousel').carousel({
        interval: 2000
    });
    carousel = true;
}

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
var petShow = false;
$(".pet1Content").click(function () {
    if (!petShow) {
        $(".pet1Detail").fadeIn();
        petShow = true;
    } else {
        $(".pet1Detail").fadeOut();
        petShow = false;
    }
})
var femaleChosen = true, maleChosen = false;
$(".femaleIconUnChosen").click(function () {
    if (!femaleChosen && maleChosen) {
        toggleFemale();
    }
})
$(".maleIconUnChosen").click(function () {
    if (femaleChosen && !maleChosen) {
        togglemale()
    }
})
function toggleFemale() {
    $(".decorateGirl,.girlBubble").fadeIn();
    $(".decorateBoy,.boyBubble").fadeOut();
    $(".femaleIconChosen ,.maleIconUnChosen").show();
    $(".femaleIconUnChosen ,.maleIconChosen").hide();
    femaleChosen = true;
    maleChosen = false;
}
function togglemale() {
    $(".decorateBoy,.boyBubble").fadeIn();
    $(".decorateGirl,.girlBubble").fadeOut();
    $(".femaleIconUnChosen ,.maleIconChosen").show();
    $(".femaleIconChosen ,.maleIconUnChosen").hide();
    femaleChosen = false;
    maleChosen = true;
}

var cloth1 = true, cap = false, hair1Front = true, hair1Back = true, shoes1 = true, girlFlower2 = false;
$(".floatElement3").click(function () {
    if (femaleChosen) {
        if (cloth1) {
            $(".girlCloth2").fadeIn();
            $(".girlCloth1,.girlSkirt1").fadeOut();
            cloth1 = false;
        } else {
            $(".girlCloth1,.girlSkirt1").fadeIn();
            $(".girlCloth2").fadeOut();
            cloth1 = true;
        }
    }

})
$(".floatElement4").click(function () {
    if (femaleChosen) {
        if (shoes1) {
            $(".girlShoes2-1,.girlShoes2-2").fadeIn();
            $(".girlShoes1-1,.girlShoes1-2").fadeOut();
            shoes1 = false;
        } else {
            $(".girlShoes1-1,.girlShoes1-2").fadeIn();
            $(".girlShoes2-1,.girlShoes2-2").fadeOut();
            shoes1 = true;
        }
    }

})
$(".floatElement1").click(function () {
    if (femaleChosen) {
        if (hair1Front && hair1Back) {
            $(".girlHair2Back,.girlHair2Front").fadeIn();
            $(".girlHair1Back,.girlHair1Front").fadeOut();
            hair1Front = false;
            hair1Back = false;
        } else if (!hair1Front && !hair1Back) {
            $(".girlHair1Back,.girlHair1Front").fadeIn();
            $(".girlHair2Back,.girlHair2Front").fadeOut();
            hair1Front = true;
            hair1Back = true;
        }
    }
})
$(".floatElement2").click(function () {
    if (femaleChosen) {
        if (girlFlower2) {
            $(".girlFlower1").fadeIn();
            $(".girlFlower2").fadeOut();
            girlFlower2 = false;
        } else {
            $(".girlFlower1").fadeOut();
            $(".girlFlower2").fadeIn();
            girlFlower2 = true;
        }
    }
})
$(".floatElement5").click(function () {
    if (!cap) {
        $(".girlCap").fadeIn();
        cap = true;
    } else {
        $(".girlCap").fadeOut();
        cap = false;
    }
});
var boyCloth1 = true, boyTrousers1 = true, boyHair1 = true, boyShoes1 = true, glass = false;
$(".floatElement2").click(function () {
    if (maleChosen) {
        if (boyHair1) {
            $(".boyHair2").fadeIn();
            $(".boyHair1").fadeOut();
            boyHair1 = false;
        } else {
            $(".boyHair1").fadeIn();
            $(".boyHair2").fadeOut();
            boyHair1 = true;
        }
    }
})

$(".floatElement3").click(function () {
    if (maleChosen) {
        if (boyCloth1) {
            $(".boyCloth2").fadeIn();
            $(".boyCloth1").fadeOut();
            boyCloth1 = false;
        } else {
            $(".boyCloth1").fadeIn();
            $(".boyCloth2").fadeOut();
            boyCloth1 = true;
        }
    }

})

$(".floatElement4").click(function () {
    if (maleChosen) {
        if (boyShoes1) {
            $(".boyShoes2").fadeIn();
            $(".boyShoes1").fadeOut();
            boyShoes1 = false;
        } else {
            $(".boyShoes1").fadeIn();
            $(".boyShoes2").fadeOut();
            boyShoes1 = true;
        }
    }
})

$(".floatElement5").click(function () {
    if (maleChosen) {
        if (boyTrousers1) {
            $(".boyTrousers2").fadeIn();
            $(".boyTrousers1").fadeOut();
            boyTrousers1 = false;
        } else {
            $(".boyTrousers1").fadeIn();
            $(".boyTrousers2").fadeOut();
            boyTrousers1 = true;
        }
    }
})
$(".floatElement1").click(function () {
    if (maleChosen) {
        if (!glass) {
            $(".boyGlass").fadeIn();
            glass = true;
        } else {
            $(".boyGlass").fadeOut();
            glass = false;
        }
    }
})

$(".pet1IconBgContainer,.pet4Content").click(function () {
    $(".pet1Chosen").hide();
    $(".pet1IconBgContainer .pet1Chosen").show();
    $(".petDetail").removeClass("petActive");
    $(".pet4Detail").addClass("petActive");
})
$(".pet2IconBgContainer,.pet5Content").click(function () {
    $(".pet1Chosen").hide();
    $(".pet2IconBgContainer .pet1Chosen").show();
    $(".petDetail").removeClass("petActive");
    $(".pet5Detail").addClass("petActive");
})
$(".pet3IconBgContainer,.pet6Content").click(function () {
    $(".pet1Chosen").hide();
    $(".pet3IconBgContainer .pet1Chosen").show();
    $(".petDetail").removeClass("petActive");
    $(".pet6Detail").addClass("petActive");
})
$(".pet4IconBgContainer,.pet7Content").click(function () {
    $(".pet1Chosen").hide();
    $(".pet4IconBgContainer .pet1Chosen").show();
    $(".petDetail").removeClass("petActive");
    $(".pet7Detail").addClass("petActive");
})
$(".pet5IconBgContainer,.pet1Content").click(function () {
    $(".pet1Chosen").hide();
    $(".pet5IconBgContainer .pet1Chosen").show();
    $(".petDetail").removeClass("petActive");
    $(".pet1Detail").addClass("petActive");
})
$(".pet6IconBgContainer,.pet2Content").click(function () {
    $(".pet1Chosen").hide();
    $(".pet6IconBgContainer .pet1Chosen").show();
    $(".petDetail").removeClass("petActive");
    $(".pet2Detail").addClass("petActive");
})
$(".pet7IconBgContainer,.pet3Content").click(function () {
    $(".pet1Chosen").hide();
    $(".pet7IconBgContainer .pet1Chosen").show();
    $(".petDetail").removeClass("petActive");
    $(".pet3Detail").addClass("petActive");
})

$(".page6Icon1BgContainer").click(function () {
    $(".page6FaceChosen").hide();
    $(".page6Face1Chosen").show();
    $(".page6Girl").fadeOut();
    $(".page6ShyGirl").fadeIn();
})
$(".page6Icon2BgContainer").click(function () {
    $(".page6FaceChosen").hide();
    $(".page6Face2Chosen").show();
    $(".page6Girl").fadeOut();
    $(".page6HelloGirl").fadeIn();

})
$(".page6Icon3BgContainer").click(function () {
    $(".page6FaceChosen").hide();
    $(".page6Face3Chosen").show();
    $(".page6Girl").fadeOut();
    $(".page6KissGirl").fadeIn();
})
$(".page6Icon4BgContainer").click(function () {
    $(".page6FaceChosen").hide();
    $(".page6Face4Chosen").show();
    $(".page6Girl").fadeOut();
    $(".page6FlowerGirl").fadeIn();
})

$(".cardMask").click(function () {
    $(this).fadeOut();
    setTimeout(function () {
        $(".cardFront").removeClass("frontCardDetailShow");
        $(".cardBack").removeClass("backCardDetailShow");
    }, 500);

})
$(".page2Card1Container").click(function () {
    $(".cardMask1").fadeIn();
    setTimeout(function () {
        $(".card1Front").addClass("frontCardDetailShow");
        $(".card1Back").addClass("backCardDetailShow");
    }, 1000);
})
$(".page2Card2Container").click(function () {
    $(".cardMask2").fadeIn();
    setTimeout(function () {
        $(".card2Front").addClass("frontCardDetailShow");
        $(".card2Back").addClass("backCardDetailShow");
    }, 1000);
})
$(".page2Card3Container").click(function () {
    $(".cardMask3").fadeIn();
    setTimeout(function () {
        $(".card3Front").addClass("frontCardDetailShow");
        $(".card3Back").addClass("backCardDetailShow");
    }, 1000);
})
$(".page2Card4Container").click(function () {
    $(".cardMask4").fadeIn();
    setTimeout(function () {
        $(".card4Front").addClass("frontCardDetailShow");
        $(".card4Back").addClass("backCardDetailShow");
    }, 1000);
})

function cardIn() {
    $(".page2RotateInner .page2Card1").addClass("cardScale1");
    $(".page2RotateInner .page2Card2").addClass("cardScale2");
    $(".page2RotateInner .page2Card3").addClass("cardScale3");
    $(".page2RotateInner .page2Card4").addClass("cardScale4");
    $(".page2RotateInner .page2Card5").addClass("cardScale5");
}
function cardOut() {
    $(".page2RotateInner .page2Card1").removeClass("cardScale1");
    $(".page2RotateInner .page2Card2").removeClass("cardScale2");
    $(".page2RotateInner .page2Card3").removeClass("cardScale3");
    $(".page2RotateInner .page2Card4").removeClass("cardScale4");
    $(".page2RotateInner .page2Card5").removeClass("cardScale5");
}

var page2Time;
function toggleMask() {
    var maskArry = ["page2Card1Mask", "page2Card2Mask", "page2Card3Mask", "page2Card4Mask"];
    var n = 1;
    $(".page2CardMask").fadeIn().parent(".pageCardOutContainer").removeClass("pageCardMove");
    $("." + maskArry[0]).fadeOut().parent(".pageCardOutContainer").addClass("pageCardMove");
    page2Time = setInterval(function () {
        if (n > 3) {
            n = 0;
        } else {
            $(".page2CardMask").fadeIn().parent(".pageCardOutContainer").removeClass("pageCardMove");
            $("." + maskArry[n]).fadeOut().parent(".pageCardOutContainer").addClass("pageCardMove");
            n++;
        }
    }, 3500);
};

var page1offset, page2offset, page3offset, page4offset, page5offset, page6offset, page7offset, scrollTop;
document.addEventListener('touchend', function () {
    if (Math.abs(page2offset) <= 0.29 * height && Math.abs(page1offset) > height * 0.79) {
        toggleMask();
    } else {
        clearInterval(page2Time);
    }
})

var startX, startY, newStartY;
document.addEventListener('touchstart', function (ev) {
    startX = ev.touches[0].pageX;
    startY = ev.touches[0].pageY;
    newStartY = ev.touches[0].clientY;
}, false);

document.addEventListener('touchend', function (ev) {
    var endX, endY;
    endX = ev.changedTouches[0].pageX;
    endY = ev.changedTouches[0].pageY;
    var direction = GetSlideDirection(startX, startY, endX, endY);
    switch (direction) {
        case 0:
            break;
        case 1:
            break;
        case 2:
            break;

        default:
    }
}, false);

function GetSlideDirection(startX, startY, endX, endY) {
    var dy = startY - endY;
    var result = 0;
    if (dy > 0) {
        result = 1;
    } else {
        result = 2;
    }
    return result;
}


var totalHeight, page2TitleScroll = 160, page2Opacity = 0, moveY, moveUp = false, moveDown = false,inpage3=true;;
document.addEventListener('touchmove', function (e) {
    page1offset = $(".page1").offset().top;
    page2offset = $(".page2").offset().top;
    page3offset = $(".page3").offset().top;
    page4offset = $(".page4").offset().top;
    page5offset = $(".page5").offset().top;
    page6offset = $(".page6").offset().top;
    page7offset = $(".page7").offset().top;

    moveY = e.changedTouches[0].clientY;

    if (moveY > newStartY) {
        moveUp = true;
        moveDown = false;
    } else if (moveY < newStartY) {
        moveUp = false;
        moveDown = true;
    }

    totalHeight = parseInt($(document).height());
    scrollTop = parseInt(document.querySelector("body").scrollTop);



    if (scrollTop >= totalHeight * 0.02) {
        $(".page2titleContainer").addClass('pageTitleUp');
    }
    if (scrollTop >= totalHeight * 0.17) {
        $(".page3titleContainer").addClass('pageTitleUp');
    }
    if (scrollTop >= totalHeight * 0.19) {
        $(".page3 .pageLeaf").addClass('pageLeafAnimation');
    }
    if (scrollTop >= totalHeight * 0.32) {
        $(".page4titleContainer").addClass('pageTitleUp');
    }
    if (scrollTop >= totalHeight * 0.35) {
        $(".page4 .flowerBg").addClass('pageLeafAnimation');
    }
    if (scrollTop >= totalHeight * 0.46) {
        $(".page5titleContainer").addClass('page5TitleUp');
    }
    if (scrollTop >= totalHeight * 0.48) {
        $(".page5 .page5Leaf").addClass('pageLeafAnimation');
    }
    if (scrollTop >= totalHeight * 0.60) {
        $(".page6titleContainer").addClass('pageTitleUp');
    }
    if (scrollTop >= totalHeight * 0.63) {
        $(".page6Feather").addClass("pageFeatherAnimation");
    }
    if (scrollTop >= totalHeight * 0.70) {
        $(".page7Girl").addClass("page7GirlUp");
    }
    if (scrollTop >= totalHeight * 0.72) {
        $(".page7titleContainer").addClass('page7TitleUp');
    }
    if (scrollTop >= totalHeight * 0.75) {
        $(".page7BgPlus").addClass('page7PlusAnimation');
        $(".page7DownLoadImg").addClass('page7DownloadUp');
    }

    if (scrollTop >= totalHeight * 0.09 && scrollTop <= totalHeight * 0.21) {
        cardIn();
    } else if (scrollTop > totalHeight * 0.21) {
        cardOut();
    }

    if (scrollTop >= totalHeight * 0.25 && scrollTop <= totalHeight * 0.41) {
        if(inpage3){
            $(".decorateGirl").fadeIn();
            $(".floatElement1").delay(100).fadeIn();
            $(".floatElement5").delay(500).fadeIn();
            $(".floatElement4").delay(900).fadeIn();
            $(".floatElement3").delay(900).fadeIn();
            $(".floatElement2").delay(1300).fadeIn();
            $(".genderContainer").delay(1700).fadeIn();
            inpage3=false;
        }
    } else if (scrollTop < totalHeight * 0.18 || scrollTop > totalHeight * 0.41) {
        $(".decorateGirl,.floatElement,.decorateBoy").hide();
        toggleFemale();
        inpage3=true;
    }

    if (scrollTop >= totalHeight * 0.35) {
        $(".petIconsContainer").fadeIn();
    }

    if (scrollTop >= totalHeight * 0.54) {
        $(".page5Map").removeClass("mapAnimation");
        $(".page5Person1,.page5Person2,.page5Person4,.page5Person3,.page5Person5,.page5Person6,.locationIcon1,.locationIcon2,.locationIcon3,.locationIcon4,.locationIcon5,.locationIcon6").removeClass("page5PsesonAnimation");
        $(".page5Person1,.page5Person2,.page5Person4,.page5Person3,.page5Person5,.page5Person6,.locationIcon1,.locationIcon2,.locationIcon3,.locationIcon4,.locationIcon5,.locationIcon6").addClass("page5PsesonAnimation");
        $(".page5Map").addClass("mapAnimation");
    } else if (scrollTop < totalHeight * 0.54) {
        $(".page5Person1,.page5Person2,.page5Person4,.page5Person3,.page5Person5,.page5Person6,.locationIcon1,.locationIcon2,.locationIcon3,.locationIcon4,.locationIcon5,.locationIcon6").removeClass("page5PsesonAnimation");
        $(".page5Map").removeClass("mapAnimation");
    }
})