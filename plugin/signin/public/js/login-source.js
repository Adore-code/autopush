const { defineComponent, ref, reactive,nextTick, computed, watch, onMounted, onBeforeUnmount, onUpdated, } = Vue
const app = Vue.createApp({
    data() {
        return {
            imageCodeUrl: '/app/signin/captcha/image?type=check-mobile' + new Date().getTime(),
            agreement: {
                visible: false
            },
            captcha: {
                login: null,
                smsLogin: null,
                sms: null,
            },
            smsLogin: {
                index: 0,
                countdown: 0,
                code: '',
                regionCode: '+86',
                mobile: '',
                smsCode: '',
            },
            loginInfo:{
                username: '',
                password: '',
            },
            loginButtonStatus: {
                login: false,
                checkMobile: false,
                smsLogin: false,
            },
        }
    },
    beforeMount(){},
    mounted(){
        console.log('进入 onMounted')
        this.initCaptcha()
    },
    methods: {
        hello(variable) {
            console.log('hello', variable);
        },
        register(){
            location.href = '/app/user/register' + location.search
        },
        forgetPassword(){
            location.href = '/app/user/password/reset'
        },
        readAgreement(){
            if (/Mobi|Android|iPhone/i.test(navigator.userAgent)) {
                //新标签打开
                window.open('/app/signin/agreement')
            }else{
                this.agreement.visible = true
            }

        },
        handleAgreementOk(){
            this.agreement.visible = false
        },
        handleAgreementCancel(){
            this.agreement.visible = false
        },
        initCaptcha(){
            for (let key in CaptchaParam) {
                const classObj = window[CaptchaParam[key].type];
                this.captcha[key] = new classObj(CaptchaParam[key].param);
            }
            console.log(this.captcha)
        },
        loginCaptcha(){
            this.captcha['account_login'].then((code) => {
                console.log(code)
                this.login(this.loginInfo.username, this.loginInfo.password, code, this.captcha['account_login'])
            })
        },
        login(username, password, ticket, that){
            this.loginButtonStatus.login = true
            axios.post('/app/signin/login/account', { username: username, password: password, ticket: ticket, }).then((res) => {
                console.log('login:', res);
                if (res.data.code !== 0){
                    this.$message.error(res.data.msg);
                    return;
                }

                that.close()
                let url = new URL(window.location.href);
                let redirect = url.searchParams.get('redirect');
                location.href = redirect && redirect.startsWith('/') && !redirect.startsWith('//') ? redirect : '/';

            }).catch((err) => {
                this.$message.error('验证失败');
            }).finally(() => {
                this.loginButtonStatus.login = false
                that.reset()
            })
        },
        loginSms(){
            let that = this.captcha['sms_login'];
            this.loginButtonStatus.smsLogin = true
            this.captcha['sms_login'].then((code) => {
                axios.post('/app/signin/login/smsLogin', { mobile: this.smsLogin.mobile, sms_code: this.smsLogin.code, region_code: this.smsLogin.regionCode, ticket: code }).then((res) => {
                    console.log('smsLogin:', res);
                    if (res.status !== 200){
                        this.$message.error('验证失败');
                        return;
                    }
                    if (res.data.code !== 0){
                        this.$message.error(res.data.msg);
                        return;
                    }

                    that.close()
                    let url = new URL(window.location.href);
                    let redirect = url.searchParams.get('redirect');
                    location.href = redirect && redirect.startsWith('/') && !redirect.startsWith('//') ? redirect : '/';

                }).catch((err) => {
                    this.$message.error('验证失败');
                }).finally(() => {
                    this.loginButtonStatus.smsLogin = false
                    that.reset()
                })
            })
        },
        countdown(){
            this.smsLogin.countdown--;
            if (this.smsLogin.countdown < 1) {
                return;
            }
            setTimeout(this.countdown, 1000)
        },
        smsNext(){
            this.captcha['account_check'].then((code) => {
                this.checkMobile(this.smsLogin.mobile, code, this.smsLogin.regionCode, this.captcha['account_check'])
            })
        },
        checkMobile(mobile, ticket,region_code, that){
            //使用axios发送post请求
            this.loginButtonStatus.checkMobile = true
            axios.post('/app/signin/login/checkAccount', { mobile: mobile, ticket: ticket,region_code: region_code }).then((res) => {
                console.log('login:', res);
                if (res.status !== 200){
                    this.$message.error('验证失败');
                    return;
                }

                if (res.data.code === 404){
                    this.$message.error(res.data.msg);
                    this.smsLogin.index = 0;
                    return;
                }

                if (res.data.code !== 0){
                    this.$message.error(res.data.msg);
                    return;
                }

                this.smsLogin.countdown = 60;
                this.countdown()
                this.smsLogin.index = 1;
                this.smsLogin.visible = false
                that.close()

            }).catch((err) => {
                this.smsLogin.index = 0;
                this.$message.error('验证失败');
            }).finally(() => {
                this.loginButtonStatus.checkMobile = false
                this.smsLogin.code = '';
                that.reset()
            })
        }
    }
});
app.use(ArcoVue).use(ArcoVueIcon);
app.mount("#container");
