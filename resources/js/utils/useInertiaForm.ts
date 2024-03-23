/* eslint-disable prettier/prettier */
import { router } from '@inertiajs/core'
import { computed, reactive, watch, ref } from "vue";
import { cloneDeep, isEqual } from "lodash";
// import { toFormData } from "./useFormData";



interface IValidator {
    (value: string, field: string): string | boolean;
}

type ValidationSchema = Record<string, IValidator[]>;

type IFormState = {
    // eslint-disable-next-line @typescript-eslint/ban-types
    [key: string]: string | number | null | Date | boolean | Function | Record<string,any>;
    isDirty: boolean;
    errors: Record<string, any>
    hasErrors: boolean;
    progress: null | boolean;
    data: () => Record<string, any>;
    transform: (callback: (data: Record<string, any>) => any) => IFormState;
    validationSchema: (schema: ValidationSchema) => IFormState;
    reset: (...fields: any[]) => IFormState;
    submit: (name: string, url: string, options: Record<string, any>) => void;
    validate: (schema?: ValidationSchema) => boolean;
    // eslint-disable-next-line @typescript-eslint/ban-types
    submitForm: (method: string | Function, options: Record<string, any>) => void
    submitEvent: (event: string) => void
};

type IFormConfig = {
    emit?: (event: string, data: any) => void;
    axiosInstance?: any;
};
export const useInertiaForm = (
    props: Record<string, any>,
    config: IFormConfig = {}
) => {
    const data = props || {};
    const defaults = cloneDeep(data);
    let transform = (data: Record<string, any>) => data;
    let validationSchema: ValidationSchema = {};
    let cancelToken = null
    let recentlySuccessfulTimeoutId = null

    const isLoading = ref(false);

    const form: IFormState = reactive({
        ...data,
        isDirty: false,
        errors: {},
        /* @ts-expect-error: computed problems */
        hasErrors: computed(() => Object.keys(form.errors).length > 0),
        processing: false,
        progress: null,
        wasSuccessful: false,
        recentlySuccessful: false,
        data() {
            return Object.keys(data).reduce(
                (carry: Record<string, any>, key) => {
                    carry[key] = form[key];
                    return carry;
                },
                {}
            );
        },
        transform(callback: (data: any) => any) {
            transform = callback;
            return form;
        },
        validationSchema(schema: ValidationSchema) {
            validationSchema = schema;
            return form;
        },
        reset(...fields: any[]) {
            const clonedDefaults = cloneDeep(defaults);
            if (fields.length === 0) {
                Object.assign(this, clonedDefaults);
            } else {
                Object.keys(clonedDefaults)
                    .filter((key) => fields.includes(key))
                    .forEach((key: string) => {
                        defaults[key] = clonedDefaults[key];
                        this[key] =  clonedDefaults[key];
                    }, {})
            }

            return this;
        },
        setError(fieldOrFields: keyof IFormState | Record<keyof IFormState, string>, maybeValue?: string) {
            Object.assign(this.errors, typeof fieldOrFields === 'string' ? { [fieldOrFields]: maybeValue } : fieldOrFields)

            this.hasErrors = Object.keys(this.errors).length > 0

            return this
        },
        clearErrors(...fields: any[]) {
            this.errors = Object.keys(this.errors).reduce(
              (carry, field) => ({
                ...carry,
                ...(fields.length > 0 && !fields.includes(field) ? { [field]: this.errors[field] } : {}),
              }),
              {},
            )

            this.hasErrors = Object.keys(this.errors).length > 0

            return this
        },
        submitEvent: (event: string) => {
            if (config.emit) {
                config.emit(event, transform(form.data()));
            }
        },
        // eslint-disable-next-line prettier/prettier, @typescript-eslint/ban-types
        submitForm(method: string | Function, options: Record<string, any>): void {
            const _options = {
                ...options,
                onCancelToken: (token: any) => {
                  this.cancelToken = token

                  if (options.onCancelToken) {
                    return options.onCancelToken(token)
                  }
                },
                onBefore: (visit: any) => {
                  this.wasSuccessful = false
                  this.recentlySuccessful = false
                  clearTimeout(recentlySuccessfulTimeoutId)

                  if (options.onBefore) {
                    return options.onBefore(visit)
                  }
                },
                onStart: (visit: any) => {
                  this.processing = true

                  if (options.onStart) {
                    return options.onStart(visit)
                  }
                },
                onProgress: (event: any) => {
                  this.progress = event

                  if (options.onProgress) {
                    return options.onProgress(event)
                  }
                },
                onSuccess: async (page) => {
                  this.processing = false
                  this.progress = null
                  this.clearErrors()
                  this.wasSuccessful = true
                  this.recentlySuccessful = true
                  recentlySuccessfulTimeoutId = setTimeout(() => (this.recentlySuccessful = false), 2000)

                  const onSuccess = options.onSuccess ? await options.onSuccess(page) : null
                  defaults = cloneDeep(this.data())
                  this.isDirty = false
                  return onSuccess
                },
                onError: (errors) => {
                  this.processing = false
                  this.progress = null
                  this.clearErrors().setError(errors)

                  if (options.onError) {
                    return options.onError(errors)
                  }
                },
                onCancel: () => {
                  this.processing = false
                  this.progress = null

                  if (options.onCancel) {
                    return options.onCancel()
                  }
                },
                onFinish: (visit: any) => {
                  this.processing = false
                  this.progress = null
                  cancelToken = null

                  if (options.onFinish) {
                    return options.onFinish(visit)
                  }
                },
              };

            if (typeof method === "function") {
                method("submit", transform(form.data()));
            } else {
                if (!this.validate()) {
                    isLoading.value = false;
                    return
                }
                isLoading.value = true;
                if (method == 'delete') {
                    router.delete(options.url, {..._options, data: transform(form.data()) })
                } else {
                    return router[method](options.url, transform(form.data()), _options)
                }
            }
        },

        submit(name: string, url: string,  options: Record<string, any>) {
            if (config.emit) {
                form.submitEvent(name);
            } else {
                form.submitForm(name, {url, ...options });
            }
        },
        post(url: string, options: Record<string, any>) {
          return form.submitForm('post', {
            url,
            ...options
          });
        },
        put(url: string, options: Record<string, any>) {
          return form.submitForm('put', {
            url,
            ...options
          });
        },
        patch(url: string, options: Record<string, any>) {
          return form.submitForm('patch', {
            url,
            ...options
          });
        },
        delete(url: string, options: Record<string, any>) {
          return form.submitForm('delete', {
            url,
            ...options
          });
        },
        cancel() {
            if (cancelToken) {
                cancelToken.cancel()
            }
        },

        formData() {
            return toFormData(form.data());
        },
        validate(schema: ValidationSchema = validationSchema) {
            form.errors = {};
            const fields = Object.keys(schema);
            if (fields.length) {
                fields.forEach((field) => {
                    if (
                        validationSchema[field] &&
                        validationSchema[field].length
                    ) {
                        const result = validationSchema[field]
                            .map((validator) => validator(form.data()[field], field))
                            .filter((result) => result !== true);
                        if (result.length) {
                            form.errors[field] = result[0];
                        }
                    }
                });
            }
            console.log(form.errors)
            return !form.hasErrors;
        },
    });

    watch(
        () => form,
        () => {
            form.isDirty = !isEqual(form.data(), defaults);
            if (form.hasErrors) {
                form.validate();
            }
        },
        { immediate: true, deep: true }
    );

    return form;
};

export const validators = {
    isRequired(value: string, field: string) {
        return value ? true : `The field ${field} is required`;
    },
    isUrl(value: string, field: string) {
        return ("https://" + value).match(
            /^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([/\w .-]*)*\/?$/
        )
            ? true
            : "Invalid URL";
    },
};
