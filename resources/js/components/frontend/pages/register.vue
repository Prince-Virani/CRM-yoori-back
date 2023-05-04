<template>
  <div class="sg-page-content">
    <section class="ragister-account text-center">
      <div class="container">
        <div class="account-content">
          <div class="thumb">
            <img :src="settings.sign_up_banner" loading="lazy" :alt="form.user_type" class="img-fluid img-fit"/>
          </div>
          <div class="form-content">
            <h1>{{ lang.sign_up }} </h1>
            <p v-if="otp  && !settings.disable_otp">{{ lang.enter_to_complete_registration }}</p>
            <p v-else>{{ lang.sign_to_continue_shopping }}</p>
            <h5 class="registration_heading" v-if="form.user_type == 'seller'">{{ lang.personal_info }}</h5>
            <form class="ragister-form" name="ragister-form" @submit.prevent="register">
              <div>
                <div class="form-group">
                  <span class="mdi mdi-name mdi-account-outline"></span>
                  <input type="text" v-model="form.first_name" class="form-control"
                         :class="{ 'error_border' : errors.first_name }"
                         :placeholder="lang.first_name"/>
                </div>
                <span class="validation_error" v-if="errors.first_name">{{
                    errors.first_name[0]
                  }}</span>
                <div class="form-group">
                  <span class="mdi mdi-name mdi-account-outline"></span>
                  <input type="text" v-model="form.last_name" class="form-control"
                         :class="{ 'error_border' : errors.last_name }"
                         :placeholder="lang.last_name"/>
                </div>
                <span class="validation_error" v-if="errors.last_name">{{ errors.last_name[0] }}</span>
                <div class="form-group" v-if="optionTo == 'phone'">
                  <span class="mdi mdi-name mdi-email-outline"></span>
                  <input type="email" v-model="form.email" class="form-control mb-0"
                         :class="{ 'error_border mb-0' : errors.email }" :placeholder="lang.email"/>
                </div>
                <span class="validation_error"
                      v-if="errors.email && optionTo == 'phone'">{{ errors.email[0] }}</span>

                <div v-if="optionTo == 'email' && addons.includes('otp_system')">
                  <!--                                    <vue-tel-input v-bind="bindProps" class="form-control phone_intl"-->
                  <!--                                                   :class="{ 'error_border mb-0' : errors.phone }"-->
                  <!--                                                   v-model="form.phone"></vue-tel-input>-->
                  <telePhone @phone_no="getNumber" :phone_error="errors.phone ? errors.phone[0] : null"></telePhone>

                  <span class="validation_error" v-if="errors.phone">{{ errors.phone[0] }}</span>
                </div>
                <div v-if="addons.includes('otp_system')" class="form-group text-end mb-3">
                  <a href="javascript:void(0)" class="btn sign-in-option"
                     @click="loginOptions(optionTo)">{{
                      optionTo == 'email' ? lang.use_email_instead : lang.use_phone_instead
                    }}</a>
                </div>
                <div class="form-group" v-if="optionTo == 'phone'"
                     :class="{ 'mt-4' : !addons.includes('otp_system') }">
                  <span class="mdi mdi-name mdi-lock-outline"></span>
                  <input type="password" v-model="form.password" class="form-control"
                         :class="{ 'error_border' : errors.password }" :placeholder="lang.Password"/>
                </div>
                <span class="validation_error"
                      v-if="errors.password && optionTo == 'phone'">{{ errors.password[0] }}</span>
                <div class="form-group" v-if="optionTo == 'phone'"
                     :class="{ 'mt-4' : !addons.includes('otp_system') }">
                  <span class="mdi mdi-name mdi-lock-outline"></span>
                  <input type="password" v-model="form.password_confirmation" class="form-control"
                         :class="{ 'error_border' : errors.password_confirmation }" :placeholder="lang.password_confirmation"/>
                </div>
                <span class="validation_error"
                      v-if="errors.password_confirmation && optionTo == 'phone'">{{ errors.password_confirmation[0] }}</span>
              </div>
              <div class="form-group mt-4" v-if="addons.includes('otp_system') && otp && !settings.disable_otp">
                <span class="mdi mdi-name mdi-lock-outline"></span>
                <input type="text" v-model="form.otp" class="form-control otp mb-0"
                       :class="{ 'error_border' : errors.otp }" :placeholder="lang.enter_oTP"/>
              </div>
              <div v-if="addons.includes('otp_system') && otp">
                <p class="count_down_timer" v-if="!settings.disable_otp">
                  <span v-if="otp && (minute >=0 && second >= 0)">0{{ minute }}:{{ second }}</span>
                  <span @click="registerByPhone" v-else>{{ lang.otp_request }}</span>
                </p>
              </div>

              <gdpr_page ref="customer_agreement" :agreements="settings.customer_agreement"></gdpr_page>

              <button type="submit" class="btn" v-if="otp && !loading"
                      :class="{ 'disable_btn' : form.otp.length != 5 && !settings.disable_otp }">{{ lang.sign_up }}
              </button>

              <button type="submit" class="btn" v-else-if="optionTo == 'phone' && !loading">
                {{ lang.sign_up }}
              </button>
              <loading_button v-if="loading" :class_name="'btn'"></loading_button>
              <button type="button" @click="registerByPhone" class="btn"
                      v-else-if="optionTo == 'email' && !otp">{{ lang.get_oTP }}
              </button>
              <p>{{ lang.have_an_account }}
                <router-link :to="{ name : 'login' }">{{ lang.sign_in }}</router-link>
              </p>
              <div class="continue-with"
                   v-if="settings.is_facebook_login_activated == 1 || settings.is_google_login_activated == 1 || settings.is_twitter_login_activated == 1">
                <p>{{ lang.or_continue_with }}</p>
              </div>
              <ul class="global-list">
                <li v-if="settings.is_facebook_login_activated == 1"><a class="facebook"
                                                                        href="javascript:void(0)"
                                                                        @click="loginWithSocial('fb')"><span
                    class="mdi mdi-name mdi-facebook"></span>{{ lang.facebook }}</a></li>
                <li v-if="settings.is_twitter_login_activated == 1"><a class="twitter"
                                                                       href="javascript:void(0)"
                                                                       @click="loginWithSocial('twitter')"><span
                    class="mdi mdi-name mdi-twitter"></span>{{ lang.twitter }}</a></li>
                <li v-if="settings.is_google_login_activated == 1"><a class="google"
                                                                      href="javascript:void(0)"
                                                                      @click="loginWithSocial('google')"><span
                    class="mdi mdi-name mdi-google"></span>{{ lang.google }}</a></li>
              </ul>
            </form>
            <!-- /.contact-form -->
          </div>
        </div>
        <!-- /.account-content -->
      </div>
      <!-- /.container -->
    </section>
    <!-- /.ragister-account -->
  </div>

</template>

<script>
import telePhone from "../partials/telephone";
import {FacebookAuthProvider, getAuth, GoogleAuthProvider, signInWithPopup, TwitterAuthProvider} from "firebase/auth";
import gdpr_page from "../partials/gdpr_page";

export default {
  name: "register",
  components: {
    telePhone,gdpr_page
  },
  data() {
    return {
      form: {
        first_name: '',
        last_name: '',
        email: '',
        password: '',
        password_confirmation: '',
        phone: '',
        address: '',
        phone_no: '',
        otp: '',
        user_type: this.$route.params.type,
        referral_code: this.$route.query.referral_code
      },
      optionTo: 'phone',
      social_login_active: false,
      loading: false,
      buttonText: 'Get OTP',
      phone_no: '',
      minute: 1,
      second: 60,
      otp: '',
      agreement: '',
      country_code: []
    }
  },
  watch: {
    $route(from, to) {
      this.form.user_type = from.params.type;
    }
  },

  mounted() {
    this.loginOptions();
  },
  computed: {
    loginRedirect() {
      return this.$store.getters.getLoginRedirection;
    }
  },
  methods: {
    countDownTimer() {
      this.minute = 1;
      this.second = 60;
      setInterval(() => {
        this.second--;
        if (this.second == 0) {
          this.minute--;
          this.second = 60;
          if (this.minute == 0) {
            this.minute = 0;
          }
        }
      }, 1000);
    },
    register() {
      if (!this.$refs.customer_agreement.checkAgreements()) {
        return toastr.info(this.lang.accept_terms, this.lang.Error + ' !!');
      }
      this.loading = true;
      let url = this.getUrl('register');
      this.form.real_otp = this.otp;
      if (this.form.real_otp != this.otp) {
        toastr.error(this.lang.OTP_doesnt_match, this.lang.Error + ' !!');
      }
      axios.post(url, this.form).then((response) => {
        this.loading = false;
        if (response.data.error) {
          this.$Progress.fail();
          toastr.error(response.data.error, this.lang.Error + ' !!');
        }
        if (response.data.success) {
          if (response.data.type == 1) {
            this.$store.dispatch('user', response.data.auth_user);
            this.$router.push({name: 'dashboard'});
          } else {
            this.$router.push({name: 'login'});
          }

          this.errors = [];
          toastr.success(response.data.success, this.lang.Success + ' !!');
        }
      })
          .catch((error) => {
            this.loading = false;
            this.$Progress.fail();
            if (error.response && error.response.status == 422) {
              this.errors = error.response.data.errors;
            }
          })
    },
    socialLogin(form) {
      this.social_login_active = true;
      let url = this.getUrl('social-login');
      axios.post(url, form).then((response) => {
        this.loading = false;
        this.social_login_active = false;
        if (response.data.success) {
          this.errors = [];
          if (this.loginRedirect) {
            this.$router.push({name: this.loginRedirect});
          } else {
            this.$router.push({name: 'dashboard'});

            this.$store.dispatch('carts', response.data.carts);
            this.$store.dispatch('user', response.data.user);
            this.$store.dispatch('compareList', response.data.compare_list);
            this.$store.dispatch('wishlists', response.data.wishlists);
          }
        } else {
          toastr.error(response.data.error, this.lang.Error + ' !!');
        }
      }).catch((error) => {
        this.loading = false;
        this.social_login_active = false;
        toastr.error('Something Went Wrong, Please Try Again', this.lang.Error + ' !!');
      })
    },
    loginWithSocial(type) {
      let provider = '';
      if (type == 'fb') {
        provider = new FacebookAuthProvider();
        provider.addScope('user_birthday');
        provider.addScope('user_gender');
        provider.addScope('public_profile');
      } else if(type == 'google') {
        provider = new GoogleAuthProvider();
        provider.addScope('profile');
        provider.addScope('email');
      }
      else if(type == 'twitter') {
        provider = new TwitterAuthProvider();
      }

      const auth = getAuth();

      signInWithPopup(auth, provider)
          .then((result) => {
            let raw_user = JSON.parse(result._tokenResponse.rawUserInfo);

            let credential = '';
            let picture = '';

            if (type == 'fb') {
              credential = FacebookAuthProvider.credentialFromResult(result);
              picture = raw_user.picture ? raw_user.picture.data.url : '';
            } else if(type == 'google') {
              credential = GoogleAuthProvider.credentialFromResult(result);
              picture = raw_user.picture ? raw_user.picture : '';
            }
            else if(type == 'twitter')
            {
              credential = TwitterAuthProvider.credentialFromResult(result);
              picture = raw_user.picture ? raw_user.picture : '';
            }

            const token = credential.accessToken;
            // The signed-in user info.
            const user = result.user;

            let form = {
              name: raw_user.name ? raw_user.name : '',
              email: raw_user.email ? raw_user.email : '',
              phone: raw_user.phoneNumber ? raw_user.phoneNumber : '',
              uid: user.uid,
              dob: raw_user.birthday,
              gender: raw_user.gender,
              image: picture,
            };

            this.socialLogin(form);

          }).catch((error) => {
        // Handle Errors here.
        const errorCode = error.code;
        const errorMessage = error.message;
        // The email of the user's account used.
        const email = error.customData.email;
        // The AuthCredential type that was used.
        const credential = GoogleAuthProvider.credentialFromError(error);
        // ...
      });
    },
    loginOptions(optionTo) {
      if (optionTo) {
        if (optionTo == 'phone') {
          if (this.settings.disable_otp) {
            this.otp = true;
          }
          this.buttonText = 'Get OTP';
          this.optionTo = 'email';
        } else {
          this.buttonText = 'Sign Up';
          this.optionTo = 'phone';
        }
      } else {
        if (this.addons.includes('otp_system')) {
          this.optionTo = 'email';
          if (this.settings.disable_otp) {
            this.otp = true;
          }
          this.buttonText = 'Get OTP';
        } else {
          this.buttonText = 'Sign Up';
          this.optionTo = 'phone';
        }
      }
    },
    registerByPhone() {
      this.form.email = null;
      if (!this.$refs.customer_agreement.checkAgreements()) {
        return toastr.info(this.lang.accept_terms, this.lang.Error + ' !!');
      }
      let url = this.getUrl('register/by-phone');

      this.loading = true;
      axios.post(url, this.form).then((response) => {
        this.loading = false;
        if (response.data.error) {
          toastr.error(response.data.error, this.lang.Error + ' !!');
        } else {
          toastr.success(response.data.data, this.lang.Success + ' !!');
          this.errors = [];
          this.otp = true;
          this.countDownTimer();
        }
      }).catch((error) => {
        this.loading = false;
        this.$Progress.fail();
        if (error.response && error.response.status == 422) {
          this.errors = error.response.data.errors;
        }
      });
    },
    getNumber(number) {
      this.form.phone = number;
    }
  },
}
</script>
