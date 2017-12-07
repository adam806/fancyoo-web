$(window).scroll(function () {
    var scrollTop = document.body.scrollTop || document.documentElement.scrollTop || window.pageYOffset;//调兼容性时候要加上firefox的document.documentElement.scrollTop

    if (scrollTop <= height * 0.42) {
        slide(".slideP1");
    }
    else if (scrollTop > height * 0.42 && scrollTop <= height * 0.74) {//第二屏文字
        pTitleUp(".page2Title");
    }
    else if (scrollTop > height * 0.74 && scrollTop <= height * 1.47) { //第二屏卡片
        cardScale();
        slide(".slideP2");
    }
    else if (scrollTop > height * 1.47 && scrollTop <= height * 1.89) { //第三屏文字
        pTitleUp(".p3_title");
    }
    else if (scrollTop > height * 1.89 && scrollTop <= height * 2.52) { //第三屏泡泡淡显
        showBubbles();
        leafMove(".p3_flower");
        slide(".slideP3");
    }
    else if (scrollTop > height * 2.52 && scrollTop <= height * 2.95) { //第四屏文字
        pTitleUp(".p4_title");
    }
    else if (scrollTop > height * 2.95 && scrollTop <= height * 3.58) { //第四屏宠物放大
        petScale();
        leafMove(".p4_flower");
        slide(".slideP4");
    }
    else if (scrollTop > height * 3.58 && scrollTop <= height * 3.8) { //第五屏文字
        pTitleUp(".p5_title");
    }
    else if (scrollTop > height * 3.8 && scrollTop <= height * 3.9) { //第五屏幕地图放大
        $(".map").addClass('mapScale');
    }
    else if (scrollTop > height * 3.9 && scrollTop <= height * 4.63) { //第五屏人物放大
        mapPersonScale();
        leafMove(".p5_leaf");
        slide(".slideP5");
    }
    else if (scrollTop > height * 4.63 && scrollTop <= height * 4.95) { //第六屏文字
        pTitleUp(".p6_title");
    }
    else if (scrollTop > height * 4.95 && scrollTop <= height * 5.05) { //第六屏表情容器显示
        $(".expressionContainer").fadeIn();
    }
    else if (scrollTop > height * 5.05 && scrollTop <= height * 5.69) { //第六屏书上移
        bookUp();
        featherMove();
        slide(".slideP6");
    }
    else if (scrollTop > height * 5.69 && scrollTop <= height * 5.84) { //第七屏人物上移
        girlUp();
    }
    else if (scrollTop > height * 5.84 && scrollTop <= height * 5.95) { //第七屏文字上移
        p7TitleUp();
        plusMove();
    }
    else if (scrollTop > height * 5.95 && scrollTop <= height * 6.1) { //第七屏信息上移
        p7InfoUp();
    }
    else if (scrollTop > height * 6.1 && scrollTop < height * 7) { //第七屏
        slide(".slideP7");
    }
});

function slide(currentP) {
    $(currentP).find(".sideIcon1").fadeOut()
    $(currentP).find(".sideIcon3").fadeIn();
    $($(currentP).siblings().find(".sideIcon1").show())
    $($(currentP).siblings().find(".sideIcon3").hide())
}

$(".join,.connect,.bbs,.login").mouseenter(function () {
    $(this).find(".chosenBg").show();
}).mouseleave(function () {
    $(this).find(".chosenBg").hide();
})

//$(".iosContainer").onmousemove(function(){
//    $(this).css("background-image","url(../img_pc/iosBtn2.png)");
//}).mouseleave(function(){
//    $(this).css("background-image","url(../img_pc/iosBtn1.png)");
//})


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


function cardScale() {
    $(".rotateCard1").addClass("cardScale1");
    $(".rotateCard2").addClass("cardScale2");
    $(".rotateCard3").addClass("cardScale3");
    $(".rotateCard4").addClass("cardScale4");
    $(".rotateCard5").addClass("cardScale5");
    $(".rotateCard6").addClass("cardScale6");
}

function pTitleUp(currentPtitle) {
    $(currentPtitle).addClass("pageTitleAnimation");
}

$(".cardBack").click(function () {
    $(".cardDetailFront").addClass("frontCardDetailShow");
    $(".cardDetailBack").addClass("backCardDetailShow");
})

$(".cardDetail").click(function () {
    $(".cardDetailFront").removeClass("frontCardDetailShow").css('opacity', '0');
    $(".cardDetailBack").removeClass("backCardDetailShow").css('opacity', '0');
})

$(".petContainer").mouseenter(function () {
    $(this).find(".petDetail,.petOutline").fadeIn();
}).mouseleave(function () {
    $(this).find(".petDetail,.petOutline").fadeOut();
})

$(".heleloIconContent").click(function () {
    helloAnimation();
})
$(".shyIconContent").click(function () {
    shyAnimation();
})
$(".kissIconContent").click(function () {
    kissAnimation();
})
$(".flowerIconContent").click(function () {
    flowerAnimation();
})

function helloAnimation() {
    $(".iconChosen").fadeOut();
    $(".helloIconChosen").fadeIn();
    $(".roleAction").removeClass('kissAnimation helloAnimation flowerAnimation').fadeOut().addClass("helloAnimation").fadeIn();
}

function shyAnimation() {
    $(".iconChosen").fadeOut();
    $(".shyIconChosen").fadeIn();
    $(".roleAction").removeClass('helloAnimation kissAnimation flowerAnimation').fadeOut().addClass("shyAnimation").fadeIn();
}

function kissAnimation() {
    $(".iconChosen").fadeOut();
    $(".kissIconChosen").fadeIn();
    $(".roleAction").removeClass('shyAnimation helloAnimation flowerAnimation').fadeOut().addClass("kissAnimation").fadeIn();
}

function flowerAnimation() {
    $(".iconChosen").fadeOut();
    $(".flowerIconChosen").fadeIn();
    $(".roleAction").removeClass('helloAnimation shyAnimation kissAnimation').fadeOut().addClass("flowerAnimation").fadeIn();
}

//function autoAnimation() {
//    var aniamtions = [helloAnimation(), shyAnimation(), kissAnimation(), flowerAnimation()];
//    var n = 0;
//    animations[1];
//};


var sessionid;
$(".sendCode").click(function () {
    var phoneNum = $(".phoneNumInput").val();
    sendCode(phoneNum);
})
$(".confirmRegister").click(function () {
    var verifyCodeVal = $(".confirmCodeInput").val();
    if (sessionid) {
        verifyCode(sessionid, verifyCodeVal);
    } else {
        alert('请输入验证码');
    }
})
function sendCode(phoneNum) {
    $.get('http://api3.hizizai.cn:7080/api/v2/code', {'action': 'register', 'phonenumber': phoneNum}, function (data) {
        var backMessage = data.message;
        if (/用户已经存在/.test(backMessage)) {
            alert('用户已存在');
            return false;
        } else {
            sessionid = data.sessionid;
        }
    }, 'json')
}
function verifyCode(sessionid, verifyCode) {
    $.post('http://api3.hizizai.cn:7080/api/v2/code', {'sessionid': sessionid, 'code': verifyCode}, function (data) {
        var backCode = data.code;
        if (backCode == 0) {
            var username = $(".phoneNumInput").val(), nickname = $(".registerUserInput").val(), password = $.md5($.md5($(".registerPasswordInput").val()));
            register(username, nickname, password, sessionid);//调用注册主程序
        }
    }, 'json');
}
function register(username, nickname, password, sessionid) {
    $.post('http://api3.hizizai.cn:7080/api/v2/register', {
        'username': username,
        'nickname': nickname,
        'password': password,
        'sessionid': sessionid,
        'channelid': '1',
        'signature': '1',
        'avatar': '1',
        'gender': '1'
    }, function (data) {
        if (data.token) {
            $(".registerMask").hide();
            $(".displayUserName").text(data.user.nickname);
        }
    }, 'json');

}

$(".registerBtn").click(function () {
    $(".registerMask").show();
})

$(".closeRegister").click(function () {
    $(".registerMask").hide();
})


$(".rechargeNowBtn").click(function () {
    var rechargeId = $(".rechargeInput").val(), rechargeAmount = parseInt($(".rechargeChosen").text());

    getQrcode(rechargeId, rechargeAmount);
})
var rechargeSucc = false, timeOut = false;
function getQrcode(rechargeId, rechargeAmount) {
    $.post('../../Wxpay2/example51543/native.php', {'money': rechargeAmount, 'user': rechargeId}, function (data) {
        if (data.code == 0) {
            var orderid = data.orderid, qrurl = data.code_url;
            $(".rechargeQrMask").show();
            $(".qrCode").attr('src', qrurl);

            var countDownNum = 60;
            var countDown = setInterval(function () {
                countDownNum--;
                $(".countNum").text(countDownNum);
                while (countDownNum == -1) {
                    window.clearInterval(countDown);
                    timeOut = true;
                    alert('超时，请重试！');
                    return false;
                }
            }, 1000);

            var checkPay = setInterval(function () {
                if (!timeOut) {
                    recharge(orderid);
                    if (rechargeSucc) {
                        window.clearInterval(checkPay);
                        alert('充值成功');
                        $(".rechargeMask").hide();
                    }
                } else {
                    return false;
                }
            }, 2000);

        } else if (data.code == 1) {
            alert("用户id不存在")
        }
    }, 'json');
};
function recharge(orderid) {
    $.post('../isPayOk.php', {'id': orderid}, function (data) {
        var codeSuccess = data.code;
        if (codeSuccess == 1) {
            rechargeSucc = true;
        }
    }, 'json');
}

$(".rechargeBtn").click(function () {
    $(".rechargeMask").show();
})

$(".closeRecharge").click(function () {
    $(".rechargeMask").hide();
})

$(".rechargeList").click(function () {
    $(".rechargeList").removeClass("rechargeChosen");
    $(this).addClass("rechargeChosen");
})

$(".cardWolfContainer").mouseenter(function () {
    $(this).find(".cardMask4,.cardMask2").fadeOut();
    $(this).find(".cardMask1").fadeIn();
}).mouseleave(function () {
    $(this).find(".cardMask4,.cardMask2").fadeIn();
    $(this).find(".cardMask1").fadeOut();
})
$(".cardHunterContainer").mouseenter(function () {
    $(this).find(".cardMask4,.cardMask2").fadeOut();
    $(this).find(".cardMask1").fadeIn();
}).mouseleave(function () {
    $(this).find(".cardMask4,.cardMask2").fadeIn();
    $(this).find(".cardMask1").fadeOut();
})

$(".cardWitchContainer").mouseenter(function () {
    $(this).find(".cardMask4,.cardMask2").fadeOut();
    $(this).find(".cardMask1").fadeIn();
}).mouseleave(function () {
    $(this).find(".cardMask4,.cardMask2").fadeIn();
    $(this).find(".cardMask1").fadeOut();
})

$(".cardPredictContainer").mouseenter(function () {
    $(this).find(".cardMask4,.cardMask2").fadeOut();
    $(this).find(".cardMask1").fadeIn();
}).mouseleave(function () {
    $(this).find(".cardMask4,.cardMask2").fadeIn();
    $(this).find(".cardMask1").fadeOut();
})

$(".clickMe").click(function () {
    $(".map").addClass('mapScale')
});

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


var femaleChosen = true;
var cloth1 = true, cap = false, hair1Front = true, hair1Back = true, shoes1 = true, girlFlower2 = false;
$(".floatElement4").click(function () {
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
$(".floatElement3").click(function () {
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
$(".floatElement5").click(function () {
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
$(".floatElement2").click(function () {
    if (!cap) {
        $(".girlCap").fadeIn();
        cap = true;
    } else {
        $(".girlCap").fadeOut();
        cap = false;
    }
})


/**点击男性各部分服装部件**/
var boyCloth1 = true, boyTrousers1 = true, boyHair1 = true, boyShoes1 = true, glass = false;
$(".floatElement5").click(function () {
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

$(".floatElement4").click(function () {
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

$(".floatElement3").click(function () {
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

$(".floatElement2").click(function () {
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

var girlShown = false;
function showBubbles() {
    if (!girlShown) {
        $(".decorateGirl").fadeIn();
        girlShown = true;
    }

    $(".floatElement1").delay(100).fadeIn();
    $(".floatElement2").delay(200).fadeIn();
    $(".floatElement3").delay(300).fadeIn();
    $(".floatElement4").delay(400).fadeIn();
    $(".floatElement5").delay(500).fadeIn();
    $(".genderContainer").delay(600).fadeIn();
}

function showGirl() {
    $(".decorateGirl").show();
    $(".decorateBoy").hide();
}


function petScale() {
    $(".petContainer").addClass("easeScale");
}

function mapPersonScale() {
    $(".role,.locationIcon,.building").addClass('easeScale');
}

function bookUp() {
    $(".bookContainer").addClass("bookFadeUp");
}

function girlUp() {
    $(".p7Girl").addClass("p7GirlFadeUp");
}

function p7TitleUp() {
    $(".p7Title").addClass("p7TitleUp");
}

function p7InfoUp() {
    $(".howdyInfo").addClass("p7InfoUp");
}

function leafMove(leafName) {
    $(leafName).addClass("leafMove");
}

function featherMove() {
    $(".p6_feather").addClass("featherMove");
}

function plusMove() {
    $(".p7PlusBg").addClass("page7PlusUp");
}