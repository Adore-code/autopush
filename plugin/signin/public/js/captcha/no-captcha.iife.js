var NoCaptcha=function(){"use strict";class t{constructor({}){}ok(){!this.okFun||!this.data||this.okFun(this.data)}show(){return this.data="no-captcha",this.ok(),this}close(){}then(s){return this.okFun=s,this.show(),this}reset(){}unmount(){this.close()}}return t}();
