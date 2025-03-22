const { defineComponent, ref, reactive,nextTick, computed, watch, onMounted, onBeforeUnmount, onUpdated, } = Vue
const app = Vue.createApp({
    data() {
        return {
            imageCodeUrl: '/app/signin/captcha/image?type=check-mobile' + new Date().getTime(),
            agreement: {
                visible: false
            },
            captcha: {
                visible: false,
                countdown: 0,
                code: '',
                callback: null, //回调函数

            },
            smsLogin: {
                index: 0,
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
                captcha: false,
            },
        }
    },
    beforeMount(){},
    mounted(){
        console.log('进入 onMounted')
    },
    methods: {
        hello(variable) {
            console.log('hello', variable);
        },
        register(){
            location.href = '/app/ai/user/register' + location.search
        },
        forgetPassword(){
            location.href = '/app/user/password/reset'
        },
        rounded(){
            this.imageCodeUrl = '/app/signin/captcha/image?type=check-mobile&v=' + new Date().getTime();
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
        handleCaptchaOk(){
            this.captcha.callback && typeof this.captcha.callback === 'function' && this.captcha.callback();
        },
        handleCaptchaCancel(){
            this.captcha.visible = false
        },
        loginCaptcha(){
            this.captcha.callback = () => {
                console.log('loginCaptcha')
                this.login(this.loginInfo.username, this.loginInfo.password, this.captcha.code)
            }
            this.captcha.visible = true
            this.rounded()
        },
        login(username, password, image_code ){
            this.loginButtonStatus.login = true
            this.loginButtonStatus.captcha = true
            axios.post('/app/signin/login/account', { username: username, password: password, image_code: image_code, }).then((res) => {
                console.log('login:', res);
                if (res.status !== 200){
                    this.$message.error('验证失败');
                    return;
                }
                if (res.data.code !== 0){
                    this.$message.error(res.data.msg);
                    return;
                }

                let url = new URL(window.location.href);
                let redirect = url.searchParams.get('redirect');
                location.href = redirect && redirect.startsWith('/') && !redirect.startsWith('//') ? redirect : '/';

            }).catch((err) => {
                this.$message.error('验证失败');
            }).finally(() => {
                this.loginButtonStatus.login = false
                this.loginButtonStatus.captcha = false
            })
        },
        loginSms(){
            this.loginButtonStatus.smsLogin = true
            axios.post('/app/signin/login/smsLogin', { mobile: this.smsLogin.mobile, sms_code: this.captcha.code, region_code: this.smsLogin.regionCode }).then((res) => {
                console.log('smsLogin:', res);
                if (res.status !== 200){
                    this.$message.error('验证失败');
                    return;
                }
                if (res.data.code !== 0){
                    this.$message.error(res.data.msg);
                    return;
                }

                let url = new URL(window.location.href);
                let redirect = url.searchParams.get('redirect');
                location.href = redirect && redirect.startsWith('/') && !redirect.startsWith('//') ? redirect : '/';

            }).catch((err) => {
                this.$message.error('验证失败');
            }).finally(() => {
                this.loginButtonStatus.smsLogin = false
            })
        },
        countdown(){
            this.captcha.countdown--;
            if (this.captcha.countdown < 1) {
                return;
            }
            setTimeout(this.countdown, 1000)
        },
        smsNext(){
            this.captcha.callback = () => {
                console.log('smsNext')
                this.checkMobile(this.smsLogin.mobile, this.captcha.code, this.captcha.regionCode)
            }
            this.captcha.visible = true
            this.rounded()
        },
        checkMobile(mobile, image_code,region_code){
            //使用axios发送post请求
            this.loginButtonStatus.checkMobile = true
            this.loginButtonStatus.captcha = true
            axios.post('/app/signin/login/checkAccount', { mobile: mobile, image_code: image_code,region_code: region_code }).then((res) => {
                console.log('login:', res);
                if (res.status !== 200){
                    this.$message.error('验证失败');
                    return;
                }

                if (res.data.code === 404){
                    this.$message.error(res.data.msg);
                    this.smsLogin.index = 0;
                    this.captcha.visible = false
                    return;
                }

                if (res.data.code !== 0){
                    this.$message.error(res.data.msg);
                    return;
                }

                this.captcha.countdown = 60;
                this.countdown()
                this.smsLogin.index = 1;
                this.captcha.visible = false

            }).catch((err) => {
                this.smsLogin.index = 0;
                this.$message.error('验证失败');
            }).finally(() => {
                this.loginButtonStatus.checkMobile = false
                this.loginButtonStatus.captcha = false
                this.captcha.code = '';
                this.rounded();
            })
        }
    }
});
app.use(ArcoVue).use(ArcoVueIcon);
app.mount("#container");