<?php

return [
    "login" => [
        "show_list" =>[
            "main_title",
            "subtitle",
            "logo_text"
        ],
        "main_title" =>"登录",
        "subtitle" =>"私有的人工智能大模型平台",
        "logo_text" =>"AI ǀ 通行证"
    ],
    "register" => [
        "show_list" =>[
            "main_title",
            "subtitle",
            "logo_text"
        ],
        "main_title" =>"注册",
        "subtitle" =>"私有的人工智能大模型平台",
        "logo_text" =>"AI ǀ 通行证"
    ],
    "setting" => [
        "icp" =>"",
        "beian" =>"",
        "footer_html" =>""
    ],
    "agreement" => [
        "header" =>"欢迎您使用我们的服务！在使用本服务之前，请您仔细阅读以下用户协议。通过使用本服务，即表示您已阅读、理解并同意接受以下条款：",
        "body" =>"服务内容：本服务是指我们提供的各种互联网服务，包括但不限于网站、移动应用等。我们将尽最大努力为您提供稳定、安全、高效的服务。\n用户行为：您在使用本服务时，必须遵守国家法律法规，不得利用本服务从事违法活动。您不得利用本服务进行任何违法、侵权或其他损害他人利益的行为。\n用户权利和义务：您有权根据本服务的规定享受相应的服务，并有义务妥善保管自己的账号和密码，不得将账号转让或出借他人使用。\n隐私保护：我们将严格保护您的个人隐私信息，不会将您的个人信息泄露给第三方，除非经过您的同意或法律规定。\n免责声明：在法律允许的范围内，我们不对因不可抗力、计算机病毒、黑客攻击等原因导致的服务中断、数据丢失等情况负责。\n协议修改：我们有权根据实际情况对本协议进行修改，并会在网站或移动应用上公布修改后的协议内容。您在使用本服务时，应及时关注并遵守最新的协议内容。\n其他条款：本协议适用中华人民共和国法律，并由我们和您共同遵守。如有任何争议，应当协商解决，协商不成的，应当向我们所在地的人民法院提起诉讼。",
        "footer" =>"感谢您阅读以上协议内容，如果您对协议内容有任何疑问或建议，请随时与我们联系。祝您使用愉快！"
    ],
    "captcha" => [
        "captchaType" => [
            "NoCaptcha" => [
                "title" =>"关闭",
                "param" => []
            ],
            "ImageCaptcha" => [
                "title" =>"图片验证码",
                "param" => [
                    "image_code_url" => [
                        "title" =>"验证码地址",
                        "extra" =>"",
                        "disabled" =>true,
                        "defaultValue" => "/app/signin/captcha/image",
                        "access" =>[
                            "admin",
                            "public"
                        ]
                    ]
                ]
            ],
            "TxCaptcha" => [
                "title" =>"腾讯验证码",
                "param" => [
                    "secret_id" => [
                        "title" =>"SecretId",
                        "extra" =>"腾讯云SecretId",
                        "disabled" =>false,
                        "access" =>[
                            "admin"
                        ]
                    ],
                    "secret_key" => [
                        "title" =>"SecretKey",
                        "extra" =>"腾讯云SecretKey",
                        "disabled" =>false,
                        "access" =>[
                            "admin"
                        ]
                    ],
                    "captcha_app_id" => [
                        "title" =>"AppId",
                        "extra" =>"验证码里面的AppId",
                        "disabled" =>false,
                        "access" =>[
                            "admin",
                            "public"
                        ]
                    ],
                    "app_secret_key" => [
                        "title" =>"AppSecretKey",
                        "extra" =>"验证码里面的AppSecretKey",
                        "disabled" =>false,
                        "access" =>[
                            "admin"
                        ]
                    ]
                ]
            ]
        ],
        "setting" => [
            "account_login" => [
                "title" =>"账号登录",
                "type" =>"ImageCaptcha",
                "param" => [
                    "image_code_url" =>"/app/signin/captcha/image"
                ]
            ],
            "account_check" => [
                "title" =>"账号验证",
                "type" =>"ImageCaptcha",
                "param" => [
                    "image_code_url" =>"/app/signin/captcha/image"
                ]
            ],
            "sms_login" => [
                "title" =>"短信登录",
                "type" =>"NoCaptcha",
                "param" => []
            ]
        ]
    ]
];
