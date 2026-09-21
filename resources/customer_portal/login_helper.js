document.addEventListener("DOMContentLoaded", () => {
    function stripLeadingHttpStatus(text) {
        if (text == null) {
            return text;
        }
        return String(text).replace(/^\d{3}\s+/, "");
    }

    const registrationForm = document.getElementById("fstRegistrationForm");
    const resetPasswordForm = document.getElementById("fstResetPasswordForm");
    const reCaptchaSettingsData =
        window.fluentSupportPublic.reCaptchaSettingsData;

    function toggleLoading(submitBtn) {
        submitBtn.classList.toggle("loading");
        submitBtn.disabled = !submitBtn.disabled;
    }

    if (registrationForm) {
        registrationForm.addEventListener("submit", (event) => {
            event.preventDefault();
            handleFormSubmission(
                registrationForm,
                "fst_submit",
                window.fluentSupportPublic.signup
            );
        });
    }

    if (resetPasswordForm) {
        resetPasswordForm.addEventListener("submit", (event) => {
            event.preventDefault();
            handleFormSubmission(
                resetPasswordForm,
                "fst_reset_pass",
                window.fluentSupportPublic.resetPass
            );
        });
    }

    if (document.getElementById("fs_show_signup")) {
        document
            .getElementById("fs_show_signup")
            .addEventListener("click", function (event) {
                fsToggleForms(event, this, ".fst_registration_wrapper");
            });
    }

    //Get element for login form and show recaptcha if enabled
    if (document.getElementById('fst_login_form')) {
        /*If Recaptcha Integration is enabled for login form*/
        if (reCaptchaSettingsData.is_enabled === 'true' && reCaptchaSettingsData.formContainingReCaptcha["login_form"] === "yes") {
            const captchaContainer = "#fst_login_form form .login-submit";
            const fieldName = "g-recaptcha-login";
            handleRecaptcha(captchaContainer, fieldName, "login");
        }
    }
    //Get element for Sign Up form and show recaptcha if enabled
    if (document.getElementById('fstRegistrationForm')) {
        /*If Recaptcha Integration is enabled for signup form*/
        if (reCaptchaSettingsData.is_enabled === 'true' && reCaptchaSettingsData.formContainingReCaptcha["signup_form"] === "yes") {
            const captchaContainer =
                ".fst_registration_wrapper form #fst_submit";
            const fieldName = "g-recaptcha-signup";
            handleRecaptcha(captchaContainer, fieldName, "signup");
        }
    }

    if (document.getElementById("fs_show_reset_password")) {
        document
            .getElementById("fs_show_reset_password")
            .addEventListener("click", function (event) {
                fsToggleForms(event, this, ".fst_reset_pass_wrapper");
            });
    }

    if (document.getElementById("fs_show_login")) {
        document
            .getElementById("fs_show_login")
            .addEventListener("click", function (event) {
                fsToggleForms(event, this, ".fst_login_wrapper");

            });
    }

    function handleFormSubmission(form, submitBtnId, reqUrl) {
        const submitBtn = document.getElementById(submitBtnId);
        toggleLoading(submitBtn);

        document.querySelectorAll(".error.text-danger").forEach((e) => {
            e.parentNode.parentNode.classList.remove("is-error");
            e.remove();
        });

        form.querySelectorAll(".success.text-success").forEach((e) => {
            e.remove();
        });

        const data = new FormData(form);

        const request = new XMLHttpRequest();
        const twoFaForm = document.getElementById('fst_login_form');

        request.open("POST", reqUrl, true);
        request.responseType = "json";
        request.setRequestHeader(
            "X-WP-Nonce",
            window.fluentSupportPublic.nonce
        );

        request.onload = function () {
            if (this.response.verification_html) {
                let html = this.response.verification_html;

                // append html to registrationForm dom
                let el = document.createElement("div");
                el.innerHTML = html;
                registrationForm.appendChild(el);

                let regFields = registrationForm.getElementsByClassName('fst_registration_fields');
                // hide regFields with css hidden inline css
                for (let i = 0; i < regFields.length; i++) {
                    regFields[i].style.display = 'none';
                }
                let regButton = registrationForm.querySelector('button'); // Adjust the selector as needed to target the specific button
                if (regButton) {
                    regButton.style.display = 'none';
                }

                // Remove or hide reCAPTCHA
                let recaptchaContainer = registrationForm.querySelector('.g-recaptcha');
                if (recaptchaContainer) {
                    recaptchaContainer.parentNode.removeChild(recaptchaContainer);
                }
            } else if (this.status === 200) {
                 if (this.response.redirect) {
                    window.location.href = this.response.redirect;
                } else if (this.response.message) {
                    let el = document.createElement("div");
                    el.classList.add("success", "text-success");
                    el.innerHTML = this.response.message;
                    form.appendChild(el);
                } else {
                    window.location.reload();
                }
            } else {
                let genericError = this.response.error;

                if (!genericError && this.response.message) {
                    genericError = this.response.message;
                } else if (genericError && this.response.data.status === 403 || this.status === 422) {
                    genericError = this.response.message;
                }

                if (genericError) {
                    let el = document.createElement("div");
                    el.classList.add("error", "text-danger");
                    el.textContent = stripLeadingHttpStatus(genericError);

                    form.appendChild(el);
                } else {
                    for (const property in this.response) {
                        const field = document.getElementById(
                            "fst_" + property
                        );
                        if (field) {
                            let el = document.createElement("div");
                            el.classList.add("error", "text-danger");
                            el.textContent = String(Object.values(
                                this.response[property]
                            )[0]);
                            field.parentNode.insertBefore(
                                el,
                                field.nextSibling
                            );
                            field.parentNode.parentNode.classList.add(
                                "is-error"
                            );
                        }
                    }
                }
            }

            toggleLoading(submitBtn);
        };

        request.send(data);
    }

    function fsToggleForms(event, that, target) {
        event.preventDefault();
        that.parentNode.parentNode.classList.toggle("hide");
        document.querySelector(target).classList.toggle("hide");

        if ('recaptcha_v3' === reCaptchaSettingsData.reCaptcha_version) {
            handleRecaptchaBadgeVsibility(target)
        }
    }

    /*Handle Recaptcha V2 and V3*/
    function handleRecaptcha(captchaContainer, fieldName, action) {

        const recaptchaVersion = reCaptchaSettingsData.reCaptcha_version;
        const reCaptchaSiteKey = reCaptchaSettingsData.siteKey;

        var inputContainer = document.querySelector(captchaContainer);

        if ("recaptcha_v2" === recaptchaVersion) {
            var newInputHTML =
                '<div class="g-recaptcha" name="g-recaptcha-response" data-sitekey=' +
                reCaptchaSiteKey +
                "></div><br>";
        } else {

            var newInputHTML =
                '<input type="hidden" name="g-recaptcha-response" id=' +
                fieldName +
                "></input>";
            grecaptcha.ready(function () {
                grecaptcha
                    .execute(reCaptchaSiteKey, {
                        action: action,
                    })
                    .then(function (token) {

                        document.getElementById(fieldName).value = token;
                        if (reCaptchaSettingsData.formContainingReCaptcha["login_form"] === "yes") {
                            document.querySelector('.grecaptcha-badge').style.visibility = 'visible';
                        }
                    });
            });

        }
        inputContainer.insertAdjacentHTML("beforebegin", newInputHTML);

        return true;

    }

    /*End Handle Recaptcha V2 and V3*/

    /*Handle Recaptcha badge visibility in V3*/
    function handleRecaptchaBadgeVsibility(target) {
        const isSignupForm = reCaptchaSettingsData.formContainingReCaptcha["signup_form"] === "yes";
        const isLoginForm = reCaptchaSettingsData.formContainingReCaptcha["login_form"] === "yes";

        switch (target) {
            case '.fst_registration_wrapper':
                document.querySelector('.grecaptcha-badge').style.visibility = isSignupForm ? 'visible' : 'hidden';
                break;
            case '.fst_login_wrapper':
                document.querySelector('.grecaptcha-badge').style.visibility = isLoginForm ? 'visible' : 'hidden';
                break;
            default:
                document.querySelector('.grecaptcha-badge').style.visibility = 'hidden';
                break;
        }
    }

    function init2FaForm() {
        const twoFaForm = document.getElementById('fst_login_form');
        if (twoFaForm) {
            twoFaForm.addEventListener('submit', (event) => {
                event.preventDefault();
                const loginForm = document.querySelectorAll("#fst_login_form form");
                const reqUrl = window.fluentSupportPublic.fs_two_fa;

                handleFormSubmission(loginForm[0], 'fs_2fa_confirm', reqUrl);
            });
        }
    }

    /*End Handle Recaptcha badge visibility in V3*/

    const loginForm = document.querySelectorAll("#fst_login_form form");

    if (loginForm && loginForm[0]) {
        const form = loginForm[0];
        form.addEventListener("submit", (event) => {
            event.preventDefault();

            const submitBtn = document.getElementById("wp-submit");
            toggleLoading(submitBtn);

            document.querySelectorAll(".error.text-danger").forEach((e) => {
                e.parentNode.parentNode.classList.remove("is-error");
                e.remove();
            });

            const data = new FormData(loginForm[0]);
            data.append(
                "_support_login_nonce",
                window.fluentSupportPublic.fsupport_login_nonce
            );

            const request = new XMLHttpRequest();

            request.open("POST", window.fluentSupportPublic.login, true);
            request.responseType = "json";
            data.append(
                "_support_login_nonce",
                window.fluentSupportPublic.fsupport_login_nonce
            );

            request.onload = function () {
                if (this.status === 200) {
                    if (this.response.load_2fa) {
                        document.getElementById('fst_login_form').innerHTML = this.response.two_fa_form;
                        init2FaForm();
                    } else if (this.response.redirect) {
                        window.location.href = this.response.redirect;
                        setTimeout(function () {
                            window.location.reload();
                        }, 1500);
                    } else {
                        window.location.href = window.fluentSupportPublic.redirect_fallback;
                    }
                } else {
                    let genericError = this.response.error;

                    if (this.response && this.status === 403 || this.status === 422) {
                        genericError = this.response.message;
                    }

                    if (genericError) {
                        let submitWrapper =
                            document.getElementsByClassName("login-submit");
                        if (submitWrapper.length) {
                            submitWrapper = submitWrapper[0];
                        } else {
                            submitWrapper = form;
                        }

                        let el = document.createElement("div");
                        el.classList.add("error", "text-danger");
                        el.textContent = stripLeadingHttpStatus(genericError);

                        submitWrapper.parentNode.insertBefore(
                            el,
                            submitWrapper.nextSibling
                        );
                    } else {
                        for (const property in this.response) {
                            const field = document.getElementById(
                                "fst_" + property
                            );

                            if (field) {
                                let el = document.createElement("div");
                                el.classList.add("error", "text-danger");
                                el.textContent = String(Object.values(
                                    this.response[property]
                                )[0]);
                                field.parentNode.insertBefore(
                                    el,
                                    field.nextSibling
                                );
                                field.parentNode.parentNode.classList.add(
                                    "is-error"
                                );
                            }
                        }
                    }
                }

                toggleLoading(submitBtn);
            };

            request.send(data);
        });
    }
});
