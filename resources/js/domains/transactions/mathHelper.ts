import exactMath from 'exact-math';

export const MathHelper = {
    sub(num1: number, num2: number) {
        return exactMath.sub(num1, num2);
    },

    mul(num1: number, num2: number) {
        return exactMath.mul(num1, num2);
    },

    sum(num1: number, num2: number) {
        return exactMath.add(num1, num2);
    },

    sumWithRounding(num1: number, num2: number) {
      return this.rounding(this.sum(num1, num2));
    },

    subWithRounding(num1: number, num2: number) {
        return this.rounding(this.sub(num1, num2))
    },

    mulWithRounding(num1: number, num2: number) {
        return this.rounding(this.mul(num1, num2))
    },
    rounding(amount: number) {
        const result = exactMath.round(amount, -2)
        return result < 0 ? 0 : result;
    },
    
    loanPayment({ capital, interestRate, installments}) {
       const result = capital * exactMath.div(interestRate, exactMath.sub(1, Math.pow(exactMath.add(1, interestRate), -installments)))
        return this.rounding(result);
    }
}