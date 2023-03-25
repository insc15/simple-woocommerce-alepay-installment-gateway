jQuery(document).ready(function($) {
    const installmentData = []

    for (var key in installmentInfo) {
        installmentData.push(installmentInfo[key])
    }

    jQuery("body").on("change", "#mfig input[name=bankCode]", function() {
        const cards = []

        const [currentBank] = installmentData.filter((item) => {
            return item.bankCode === $(this).val()
        })
        
        currentBank.paymentMethods.forEach((item) => {
            cards.push(`
                <label class="mfig__choice">
                    <input type="radio" name="paymentMethod" value="${item.paymentMethod}" />
                    <img src="${item.logo}" alt="${item.paymentMethod}" />
                </label>
            `)
        })

        $('#mfig > *').not('.mfig__step-bank').remove();
        $('#mfig').append(`
            <div class="mfig__step mfig__step-card">
                <p class="mfig__label">Bước 2: Chọn loại thẻ</p>
                <div class="mfig__choices-container">
                    ${cards.join('')}
                </div>
            </div>
        `);
    });

    jQuery("body").on("change", "#mfig input[name=paymentMethod]", function() {
        const [currentBank] = installmentData.filter((item) => {
            return item.bankCode === $('#mfig input[name=bankCode]:checked').val()
        })

        const [currentPaymentMethod] = currentBank.paymentMethods.filter((item) => {
            return item.paymentMethod === $(this).val()
        })

        const periods = []

        currentPaymentMethod.periods.forEach((item) => {
            periods.push(`
                <label class="mfig__choice">
                    <input type="radio" name="period" value="${item.month}" />
                    <span><strong>${item.month}</strong> tháng</span>
                </label>
            `)
        })

        $('#mfig > *').not('.mfig__step-bank').not('.mfig__step-card').remove();

        $('#mfig').append(`
            <div class="mfig__step mfig__step-period">
                <p class="mfig__label">Bước 3: Chọn kỳ trả góp</p>
                <div class="mfig__choices-container">
                    ${periods.join('')}
                </div>
            </div>
        `);
    });

    jQuery("body").on("change", "#mfig input[name=period]", function() {
        const [currentBank] = installmentData.filter((item) => {
            return item.bankCode === $('#mfig input[name=bankCode]:checked').val()
        })

        const [currentPaymentMethod] = currentBank.paymentMethods.filter((item) => {
            return item.paymentMethod === $('#mfig input[name=paymentMethod]:checked').val()
        })

        const [currentPeriod] = currentPaymentMethod.periods.filter((item) => {
            return item.month == $(this).val()
        })

        $('#mfig > *').not('.mfig__step-bank').not('.mfig__step-period').not('.mfig__step-card').remove();

        $('#mfig').append(`
            <ul class="mfig__period">
                <li>
                    <strong>Góp mỗi tháng</strong>
                    <span class="amountByMonth">${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(currentPeriod.amountByMonth)}</span>
                </li>
                <li>
                    <strong>Tổng tiền trả góp</strong>
                    <span class="amountFinal">${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(currentPeriod.amountFinal)}</span>
                </li>
                <li>
                    <strong>Chênh lệch so với giá ban đầu</strong>
                    <span class="amountDiff">${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(currentPeriod.amountFinal - orderTotal)}</span>
                </li>
            </ul>
        `);
    });
});