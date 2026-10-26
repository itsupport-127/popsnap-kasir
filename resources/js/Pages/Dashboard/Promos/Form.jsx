import React, { useState } from "react";
import { Head, useForm } from "@inertiajs/react";
import axios from "axios";
import Button from "@/Components/Dashboard/Button";
import {
    IconArrowLeft,
    IconChartInfographic,
    IconDeviceFloppy,
    IconPlus,
    IconTrash,
    IconBolt,
    IconTicket,
} from "@tabler/icons-react";

const targetOptions = [
    { value: "all", label: "Semua Produk" },
    { value: "product", label: "Produk Tertentu" },
    { value: "category", label: "Kategori Tertentu" },
];

const customerScopeOptions = [
    { value: "all", label: "Semua Pelanggan" },
    { value: "walk_in", label: "Tanpa Pelanggan / Non-Member" },
    { value: "registered", label: "Pelanggan Terdaftar" },
    { value: "member", label: "Member Loyalty" },
];

const discountTypeOptions = [
    { value: "percentage", label: "Persentase (%)" },
    { value: "fixed_amount", label: "Potongan Nominal" },
    { value: "fixed_price", label: "Harga Final" },
];

function InputError({ message }) {
    if (!message) return null;
    return <p className="mt-1 text-xs text-rose-500">{message}</p>;
}

function CardSection({ title, description, children }) {
    return (
        <section className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <div className="mb-4">
                <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
                    {title}
                </h2>
                {description && (
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                        {description}
                    </p>
                )}
            </div>
            {children}
        </section>
    );
}

const inputClass =
    "h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200";

export default function Form({
    mode = "create",
    promo = null,
    products = [],
    categories = [],
    customers = [],
    tierOptions = [],
    kindOptions = [],
    activationOptions = [],
    scopeOptions = [],
}) {
    const isEdit = mode === "edit";
    const { data, setData, post, put, processing, errors } = useForm({
        name: promo?.name ?? "",
        discount_kind: promo?.discount_kind ?? "standard_discount",
        activation_mode: promo?.activation_mode ?? "auto",
        code: promo?.code ?? "",
        customer_id: promo?.customer_id ? String(promo.customer_id) : "",
        max_uses: promo?.max_uses ? String(promo.max_uses) : "",
        is_active: Boolean(promo?.is_active ?? true),
        priority: String(promo?.priority ?? 100),
        target_type: promo?.target_type ?? "all",
        product_id: promo?.product_id ? String(promo.product_id) : "",
        category_id: promo?.category_id ? String(promo.category_id) : "",
        customer_scope: promo?.customer_scope ?? "all",
        payment_method_restriction: promo?.payment_method_restriction ?? "",
        usage_limit_mode: promo?.usage_limit_mode ?? "",
        eligible_loyalty_tiers: promo?.eligible_loyalty_tiers ?? [],
        discount_type: promo?.discount_type ?? "percentage",
        discount_value:
            promo?.discount_value !== undefined && promo?.discount_value !== null
                ? String(promo.discount_value)
                : "",
        minimum_order: promo?.minimum_order ? String(promo.minimum_order) : "0",
        applies_to_qty: promo?.applies_to_qty ? String(promo.applies_to_qty) : "",
        discount_scope: promo?.discount_scope ?? "per_item",
        allow_zero_price: promo?.allow_zero_price ?? false,
        buy_qty: promo?.buy_qty ? String(promo.buy_qty) : "1",
        get_qty: promo?.get_qty ? String(promo.get_qty) : "1",
        get_product_id: promo?.get_product_id ? String(promo.get_product_id) : "",
        get_discount_percent:
            promo?.get_discount_percent !== undefined && promo?.get_discount_percent !== null
                ? String(promo.get_discount_percent)
                : "100",
        preview_quantity_multiplier: String(promo?.preview_quantity_multiplier ?? 1),
        starts_at: promo?.starts_at
            ? new Date(promo.starts_at).toISOString().slice(0, 16)
            : "",
        ends_at: promo?.ends_at
            ? new Date(promo.ends_at).toISOString().slice(0, 16)
            : "",
        notes: promo?.notes ?? "",
        qty_breaks: promo?.qty_breaks?.length
            ? promo.qty_breaks.map((item) => ({
                  min_qty: String(item.min_qty),
                  discount_type: item.discount_type,
                  discount_value: String(item.discount_value),
                  sort_order: String(item.sort_order ?? 0),
              }))
            : [{ min_qty: "3", discount_type: "percentage", discount_value: "", sort_order: "0" }],
        bundle_items: promo?.bundle_items?.length
            ? promo.bundle_items.map((item) => ({
                  product_id: String(item.product_id),
                  quantity: String(item.quantity),
                  sort_order: String(item.sort_order ?? 0),
              }))
            : [
                  { product_id: "", quantity: "1", sort_order: "0" },
                  { product_id: "", quantity: "1", sort_order: "1" },
              ],
        buy_get_items: promo?.buy_get_items?.length
            ? promo.buy_get_items.map((item) => ({
                  product_id: String(item.product_id),
                  role: item.role,
                  quantity: String(item.quantity),
                  sort_order: String(item.sort_order ?? 0),
              }))
            : [
                  { product_id: "", role: "buy", quantity: "1", sort_order: "0" },
                  { product_id: "", role: "get", quantity: "1", sort_order: "1" },
              ],
    });
    const [previewState, setPreviewState] = useState({
        loading: false,
        data: null,
    });

    const isManual = data.activation_mode === "manual";
    const isAuto = data.activation_mode === "auto";
    const isBogoManualGeneric = data.discount_kind === "buy_x_get_y" && isManual;
    const isBogoAuto = data.discount_kind === "buy_x_get_y" && isAuto;

    const validateBeforeSubmit = () => {
        const problems = [];

        if (!data.name || !data.name.trim()) {
            problems.push("Nama Promo wajib diisi.");
        }

        if (
            (data.discount_kind === "standard_discount" || data.discount_kind === "qty_break") &&
            (data.discount_value === "" || data.discount_value === null || Number(data.discount_value) <= 0)
        ) {
            problems.push("Nilai Diskon wajib diisi (lebih dari 0) untuk jenis promo ini.");
        }

        if (data.target_type === "product" && !data.product_id) {
            problems.push("Pilih Produk untuk Target 'Produk Tertentu'.");
        }

        if (data.target_type === "category" && !data.category_id) {
            problems.push("Pilih Kategori untuk Target 'Kategori Tertentu'.");
        }

        if (data.discount_kind === "qty_break") {
            const hasEmptyBreak = data.qty_breaks.some(
                (b) => !b.min_qty || b.discount_value === "" || b.discount_value === null
            );
            if (data.qty_breaks.length === 0 || hasEmptyBreak) {
                problems.push("Lengkapi semua baris Grosir Bertingkat (min. qty & nilai diskon).");
            }
        }

        if (data.discount_kind === "bundle_price") {
            const filled = data.bundle_items.filter((b) => b.product_id);
            if (filled.length < 2) {
                problems.push("Bundle Price butuh minimal 2 produk yang sudah dipilih.");
            }
        }

        if (isBogoAuto && data.target_type !== "all") {
            const buyFilled = data.buy_get_items.filter(
                (b) => b.role === "buy" && b.product_id
            );
            const getFilled = data.buy_get_items.filter(
                (b) => b.role === "get" && b.product_id
            );
            if (buyFilled.length === 0 || getFilled.length === 0) {
                problems.push("Beli X Gratis Y butuh minimal 1 produk Beli dan 1 produk Gratis yang sudah dipilih.");
            }
        }

        return problems;
    };

    const submit = (event) => {
        event.preventDefault();

        const problems = validateBeforeSubmit();
        if (problems.length > 0) {
            alert("Form belum lengkap:\n\n- " + problems.join("\n- "));
            return;
        }

        if (isEdit) {
            put(route("promos.update", promo.id));
            return;
        }

        post(route("promos.store"));
    };

    const updateArrayRow = (key, index, field, value) => {
        const next = [...data[key]];
        next[index] = { ...next[index], [field]: value };
        setData(key, next);
    };

    const addRow = (key, template) => {
        setData(key, [...data[key], template]);
    };

    const removeRow = (key, index) => {
        setData(
            key,
            data[key].filter((_, currentIndex) => currentIndex !== index)
        );
    };

    const runPreview = async () => {
        setPreviewState({ loading: true, data: null });

        try {
            const response = await axios.post(route("promos.preview"), data);
            setPreviewState({ loading: false, data: response.data?.data ?? null });
        } catch {
            setPreviewState({ loading: false, data: null });
        }
    };

    const previewGroups = previewState.data?.applied_groups || [];
    const previewVoucherDiscount = previewState.data?.summary?.voucher_discount_total || 0;
    const previewFreeItems = previewState.data?.summary?.voucher_free_items || [];

    return (
        <>
            <Head title={isEdit ? "Edit Promo" : "Buat Promo"} />

            <div className="w-full">
                <div className="mb-6">
                    <Button
                        type="link"
                        href={route("promos.index")}
                        icon={<IconArrowLeft size={18} />}
                        className="mb-3 border-none bg-transparent px-0 text-slate-500 shadow-none hover:bg-transparent hover:text-primary-600 dark:text-slate-400"
                        label="Kembali ke promo"
                    />
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                        {isEdit ? "Edit Promo" : "Buat Promo"}
                    </h1>
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                        Satu form untuk semua jenis diskon — nominal, persen, grosir
                        bertingkat, bundle, dan beli-gratis. Beda cuma cara aktifnya.
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    {Object.keys(errors).length > 0 && (
                        <div className="rounded-2xl border-2 border-rose-300 bg-rose-50 p-4 dark:border-rose-900/60 dark:bg-rose-950/30">
                            <p className="mb-2 text-sm font-semibold text-rose-700 dark:text-rose-300">
                                Form belum bisa disimpan, periksa kembali:
                            </p>
                            <ul className="list-inside list-disc space-y-1 text-sm text-rose-600 dark:text-rose-400">
                                {Object.entries(errors).map(([field, message]) => (
                                    <li key={field}>{message}</li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <CardSection
                        title="Informasi Promo"
                        description="Identitas dasar dan jenis diskon yang dipakai."
                    >
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="md:col-span-2">
                                <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Nama Promo
                                </label>
                                <input
                                    type="text"
                                    value={data.name}
                                    onChange={(event) =>
                                        setData("name", event.target.value)
                                    }
                                    placeholder="Contoh: Diskon Akhir Bulan"
                                    className={inputClass}
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="md:col-span-2">
                                <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Jenis Diskon
                                </label>
                                <div className="grid grid-cols-2 gap-2 md:grid-cols-4">
                                    {kindOptions.map((option) => (
                                        <button
                                            key={option.value}
                                            type="button"
                                            onClick={() => setData("discount_kind", option.value)}
                                            className={`rounded-xl border-2 px-3 py-2.5 text-sm font-medium transition-all ${
                                                data.discount_kind === option.value
                                                    ? "border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-950/30 dark:text-primary-300"
                                                    : "border-slate-200 text-slate-600 hover:border-slate-300 dark:border-slate-700 dark:text-slate-300"
                                            }`}
                                        >
                                            {option.label}
                                        </button>
                                    ))}
                                </div>
                                <InputError message={errors.discount_kind} />
                            </div>
                            <div>
                                <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Priority
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    value={data.priority}
                                    onChange={(event) =>
                                        setData("priority", event.target.value)
                                    }
                                    className={inputClass}
                                />
                                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Kalau ada beberapa promo otomatis cocok sekaligus,
                                    yang priority lebih tinggi dipakai dulu.
                                </p>
                            </div>
                            <div>
                                <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Qty Preview POS
                                </label>
                                <input
                                    type="number"
                                    min="1"
                                    value={data.preview_quantity_multiplier}
                                    onChange={(event) =>
                                        setData(
                                            "preview_quantity_multiplier",
                                            event.target.value
                                        )
                                    }
                                    className={inputClass}
                                />
                            </div>
                        </div>
                    </CardSection>

                    <CardSection
                        title="Cara Aktivasi"
                        description="Satu pertanyaan ini yang menentukan semuanya, apapun jenis diskonnya."
                    >
                        <div className="grid gap-3 md:grid-cols-2">
                            {activationOptions.map((option) => {
                                const Icon = option.value === "manual" ? IconTicket : IconBolt;
                                const active = data.activation_mode === option.value;

                                return (
                                    <button
                                        key={option.value}
                                        type="button"
                                        onClick={() => setData("activation_mode", option.value)}
                                        className={`flex items-start gap-3 rounded-2xl border-2 p-4 text-left transition-all ${
                                            active
                                                ? "border-primary-500 bg-primary-50 dark:bg-primary-950/30"
                                                : "border-slate-200 hover:border-slate-300 dark:border-slate-700"
                                        }`}
                                    >
                                        <div
                                            className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${
                                                active
                                                    ? "bg-primary-100 text-primary-600 dark:bg-primary-900/40 dark:text-primary-300"
                                                    : "bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400"
                                            }`}
                                        >
                                            <Icon size={18} />
                                        </div>
                                        <div>
                                            <p
                                                className={`text-sm font-semibold ${
                                                    active
                                                        ? "text-primary-700 dark:text-primary-300"
                                                        : "text-slate-800 dark:text-slate-200"
                                                }`}
                                            >
                                                {option.label}
                                            </p>
                                            <p className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                                {option.value === "manual"
                                                    ? "Kasir klik tombol promo ini dulu sebelum berlaku."
                                                    : "Langsung jalan saat syarat keranjang cocok, kasir tidak perlu pilih apa-apa."}
                                            </p>
                                        </div>
                                    </button>
                                );
                            })}
                        </div>

                        {isManual && (
                            <div className="mt-4 grid gap-4 border-t border-slate-200 pt-4 dark:border-slate-800 md:grid-cols-2">
                                <div>
                                    <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Kode Promo{" "}
                                        <span className="text-slate-400 text-xs">
                                            (kosong = generate otomatis)
                                        </span>
                                    </label>
                                    <input
                                        type="text"
                                        value={data.code}
                                        onChange={(event) =>
                                            setData("code", event.target.value.toUpperCase())
                                        }
                                        className={inputClass}
                                    />
                                    <InputError message={errors.code} />
                                </div>
                                <div>
                                    <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Maks. Penggunaan{" "}
                                        <span className="text-slate-400 text-xs">
                                            (kosong = tidak terbatas)
                                        </span>
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        value={data.max_uses}
                                        onChange={(event) =>
                                            setData("max_uses", event.target.value)
                                        }
                                        placeholder="Contoh: 100"
                                        className={inputClass}
                                    />
                                    <InputError message={errors.max_uses} />
                                </div>
                                <div className="md:col-span-2">
                                    <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Khusus Pelanggan Tertentu{" "}
                                        <span className="text-slate-400 text-xs">
                                            (kosong = umum, berlaku sesuai scope pelanggan di bawah)
                                        </span>
                                    </label>
                                    <select
                                        value={data.customer_id}
                                        onChange={(event) =>
                                            setData("customer_id", event.target.value)
                                        }
                                        className={inputClass}
                                    >
                                        <option value="">Umum, semua sesuai scope</option>
                                        {customers.map((customer) => (
                                            <option key={customer.id} value={customer.id}>
                                                {customer.name} | {customer.no_telp || "-"} |{" "}
                                                {customer.is_loyalty_member
                                                    ? `${customer.loyalty_tier} / ${customer.loyalty_points} poin`
                                                    : "non-member"}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.customer_id} />
                                </div>
                            </div>
                        )}
                    </CardSection>

                    <CardSection
                        title="Target & Scope"
                        description="Tentukan produk/kategori yang terkena promo dan siapa yang berhak."
                    >
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Target Produk
                                </label>
                                <select
                                    value={data.target_type}
                                    onChange={(event) =>
                                        setData("target_type", event.target.value)
                                    }
                                    className={inputClass}
                                >
                                    {targetOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Scope Pelanggan
                                </label>
                                <select
                                    value={data.customer_scope}
                                    onChange={(event) =>
                                        setData("customer_scope", event.target.value)
                                    }
                                    className={inputClass}
                                >
                                    {(scopeOptions.length ? scopeOptions : customerScopeOptions).map(
                                        (option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        )
                                    )}
                                </select>
                            </div>
                            <div>
                                <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Metode Bayar
                                </label>
                                <select
                                    value={data.payment_method_restriction}
                                    onChange={(event) =>
                                        setData(
                                            "payment_method_restriction",
                                            event.target.value
                                        )
                                    }
                                    className={inputClass}
                                >
                                    <option value="">Semua metode bayar</option>
                                    <option value="cashless_only">
                                        Hanya Cashless (non-tunai)
                                    </option>
                                    <option value="cash_only">Hanya Tunai (cash)</option>
                                </select>
                                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Kalau dipilih, promo otomatis ditolak sistem
                                    kalau kasir pakai metode bayar yang nggak sesuai.
                                </p>
                                <InputError
                                    message={errors.payment_method_restriction}
                                />
                            </div>
                            <div>
                                <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Batas Pemakaian per Pelanggan
                                </label>
                                <select
                                    value={data.usage_limit_mode ?? ""}
                                    onChange={(event) =>
                                        setData(
                                            "usage_limit_mode",
                                            event.target.value || null
                                        )
                                    }
                                    className={inputClass}
                                >
                                    <option value="">
                                        Per Transaksi (boleh dipakai berkali-kali)
                                    </option>
                                    <option value="per_customer">
                                        1x per Pelanggan (seumur hidup)
                                    </option>
                                </select>
                                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    "1x per Pelanggan" — begitu pelanggan pernah
                                    berhasil pakai kode ini di transaksi manapun,
                                    kode ini nggak bisa dipakai lagi oleh
                                    pelanggan yang sama. Cocok buat kode promo
                                    undian (mis. Panjat Promo) biar nggak diklaim
                                    berkali-kali orang yang sama.
                                </p>
                                <InputError message={errors.usage_limit_mode} />
                            </div>
                            {data.target_type === "product" && (
                                <div className="md:col-span-2">
                                    <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Produk
                                    </label>
                                    <select
                                        value={data.product_id}
                                        onChange={(event) =>
                                            setData("product_id", event.target.value)
                                        }
                                        className={inputClass}
                                    >
                                        <option value="">Pilih produk</option>
                                        {products.map((product) => (
                                            <option key={product.id} value={product.id}>
                                                {product.title}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.product_id} />
                                </div>
                            )}
                            {data.target_type === "category" && (
                                <div className="md:col-span-2">
                                    <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Kategori
                                    </label>
                                    <select
                                        value={data.category_id}
                                        onChange={(event) =>
                                            setData("category_id", event.target.value)
                                        }
                                        className={inputClass}
                                    >
                                        <option value="">Pilih kategori</option>
                                        {categories.map((category) => (
                                            <option key={category.id} value={category.id}>
                                                {category.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.category_id} />
                                </div>
                            )}
                            <div>
                                <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Minimum Belanja
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    value={data.minimum_order}
                                    onChange={(event) =>
                                        setData("minimum_order", event.target.value)
                                    }
                                    className={inputClass}
                                />
                                <InputError message={errors.minimum_order} />
                            </div>
                            {data.customer_scope === "member" && (
                                <div className="md:col-span-2">
                                    <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Tier Member yang Berhak
                                    </label>
                                    <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                        {tierOptions.map((tier) => {
                                            const checked = data.eligible_loyalty_tiers.includes(
                                                tier.value
                                            );

                                            return (
                                                <label
                                                    key={tier.value}
                                                    className="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={checked}
                                                        onChange={(event) => {
                                                            const next = event.target.checked
                                                                ? [
                                                                      ...data.eligible_loyalty_tiers,
                                                                      tier.value,
                                                                  ]
                                                                : data.eligible_loyalty_tiers.filter(
                                                                      (value) =>
                                                                          value !== tier.value
                                                                  );

                                                            setData(
                                                                "eligible_loyalty_tiers",
                                                                next
                                                            );
                                                        }}
                                                    />
                                                    {tier.label}
                                                </label>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}
                        </div>
                    </CardSection>

                    {(data.discount_kind === "standard_discount" ||
                        data.discount_kind === "qty_break") && (
                        <CardSection
                            title="Nilai Diskon"
                            description="Tentukan tipe diskon yang dipakai promo ini."
                        >
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Tipe Diskon
                                    </label>
                                    <select
                                        value={data.discount_type}
                                        onChange={(event) =>
                                            setData("discount_type", event.target.value)
                                        }
                                        className={inputClass}
                                    >
                                        {discountTypeOptions.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Nilai Diskon
                                    </label>
                                    <input
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        value={data.discount_value}
                                        onChange={(event) =>
                                            setData("discount_value", event.target.value)
                                        }
                                        className={inputClass}
                                    />
                                    <InputError message={errors.discount_value} />
                                </div>
                            </div>
                            {data.discount_kind === "standard_discount" && (
                                <div className="mt-4">
                                    <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Cakupan Diskon
                                    </label>
                                    <div className="inline-flex rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                                        {[
                                            { value: "per_item", label: "Per Item" },
                                            { value: "total_transaction", label: "Total Transaksi" },
                                        ].map((option) => (
                                            <button
                                                key={option.value}
                                                type="button"
                                                onClick={() => setData("discount_scope", option.value)}
                                                className={`px-4 py-2 text-sm font-medium transition-colors ${
                                                    data.discount_scope === option.value
                                                        ? "bg-primary-600 text-white"
                                                        : "bg-white text-slate-600 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                                                }`}
                                            >
                                                {option.label}
                                            </button>
                                        ))}
                                    </div>
                                    <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Diskon nempel ke barang <strong>satu-satu</strong>. Kalau
                                        customer menang kode sekaligus beberapa kali (termasuk kode
                                        yang sama berkali-kali), tiap kemenangan otomatis
                                        dialokasikan ke barang yang berbeda — nggak numpuk ke 1
                                        barang. Cocok buat hadiah undian per-item kayak Panjat
                                        Promo.
                                    </p>
                                    <InputError message={errors.discount_scope} />
                                </div>
                            )}
                            {data.discount_kind === "standard_discount" &&
                                data.discount_scope === "per_item" && (
                                <div className="mt-4">
                                    <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Berlaku untuk Maks. Berapa Item per Kemenangan?
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        placeholder="Kosongin = semua item yang cocok target"
                                        value={data.applies_to_qty}
                                        onChange={(event) =>
                                            setData(
                                                "applies_to_qty",
                                                event.target.value
                                            )
                                        }
                                        className={inputClass}
                                    />
                                    <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        Kosongin kalau 1 kemenangan/pemakaian kode cuma nutup{" "}
                                        <strong>1 barang</strong> (paling umum — cocok buat
                                        undian). Isi angka lebih besar (misal <strong>2</strong>)
                                        kalau 1 kemenangan boleh nutup beberapa barang sekaligus.
                                    </p>
                                    <InputError message={errors.applies_to_qty} />
                                </div>
                            )}
                            {data.discount_kind === "standard_discount" &&
                                data.discount_scope === "total_transaction" && (
                                <p className="mt-4 text-xs text-slate-500 dark:text-slate-400 rounded-xl border border-slate-200 dark:border-slate-700 p-3">
                                    Mode ini motong <strong>sekaligus dari sisa subtotal</strong>{" "}
                                    seluruh barang yang cocok target (bukan per barang satu-satu).
                                    Kalau kode ini dimenangkan berkali-kali dalam 1 transaksi,
                                    tiap kemenangan menghitung dari sisa subtotal setelah
                                    kemenangan sebelumnya dipotong.
                                </p>
                            )}
                            {data.discount_kind === "standard_discount" && (
                                <div className="mt-4 flex items-start justify-between gap-4 rounded-xl border border-slate-200 dark:border-slate-700 p-3">
                                    <div>
                                        <p className="text-sm font-medium text-slate-700 dark:text-slate-300">
                                            Boleh sampai bikin barang gratis (Rp0)?
                                        </p>
                                        <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            Default <strong>tidak boleh</strong> — kalau ada
                                            beberapa voucher numpuk di 1 barang yang sama
                                            (misal 81% + 45%), sistem otomatis nyisain
                                            minimal Rp1 supaya barang itu nggak pernah kejual
                                            gratis tanpa disengaja. Nyalain toggle ini cuma
                                            kalau memang niat bikin promo &quot;gratis 1
                                            barang&quot;.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        role="switch"
                                        aria-checked={data.allow_zero_price}
                                        onClick={() =>
                                            setData(
                                                "allow_zero_price",
                                                !data.allow_zero_price
                                            )
                                        }
                                        className={`relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors ${
                                            data.allow_zero_price
                                                ? "bg-primary-600"
                                                : "bg-slate-300 dark:bg-slate-600"
                                        }`}
                                    >
                                        <span
                                            className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                                                data.allow_zero_price
                                                    ? "translate-x-6"
                                                    : "translate-x-1"
                                            }`}
                                        />
                                    </button>
                                </div>
                            )}
                        </CardSection>
                    )}

                    {data.discount_kind === "qty_break" && (
                        <CardSection
                            title="Grosir Bertingkat"
                            description="Satu promo bisa punya beberapa tingkatan qty — makin banyak beli, makin besar potongan."
                        >
                            <div className="space-y-3">
                                {data.qty_breaks.map((row, index) => (
                                    <div
                                        key={`qty-break-${index}`}
                                        className="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-4 dark:border-slate-700 dark:bg-slate-800"
                                    >
                                        <input
                                            type="number"
                                            min="1"
                                            value={row.min_qty}
                                            onChange={(event) =>
                                                updateArrayRow(
                                                    "qty_breaks",
                                                    index,
                                                    "min_qty",
                                                    event.target.value
                                                )
                                            }
                                            className="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                                            placeholder="Min qty"
                                        />
                                        <select
                                            value={row.discount_type}
                                            onChange={(event) =>
                                                updateArrayRow(
                                                    "qty_breaks",
                                                    index,
                                                    "discount_type",
                                                    event.target.value
                                                )
                                            }
                                            className="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                                        >
                                            {discountTypeOptions.map((option) => (
                                                <option key={option.value} value={option.value}>
                                                    {option.label}
                                                </option>
                                            ))}
                                        </select>
                                        <input
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            value={row.discount_value}
                                            onChange={(event) =>
                                                updateArrayRow(
                                                    "qty_breaks",
                                                    index,
                                                    "discount_value",
                                                    event.target.value
                                                )
                                            }
                                            className="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                                            placeholder="Nilai"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => removeRow("qty_breaks", index)}
                                            className="inline-flex h-11 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 dark:border-rose-900/50 dark:bg-rose-950/40"
                                        >
                                            <IconTrash size={16} />
                                        </button>
                                    </div>
                                ))}
                                <button
                                    type="button"
                                    onClick={() =>
                                        addRow("qty_breaks", {
                                            min_qty: "1",
                                            discount_type: "percentage",
                                            discount_value: "",
                                            sort_order: String(data.qty_breaks.length),
                                        })
                                    }
                                    className="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200"
                                >
                                    <IconPlus size={16} />
                                    Tambah Tingkat
                                </button>
                                <InputError message={errors.qty_breaks} />
                                <p className="text-xs text-slate-500 dark:text-slate-400">
                                    Berlaku untuk target produk/kategori di atas (atau total
                                    qty semua produk di keranjang jika target = Semua Produk).
                                </p>
                            </div>
                        </CardSection>
                    )}

                    {data.discount_kind === "bundle_price" && (
                        <CardSection
                            title="Bundle Price"
                            description="Pilih kombinasi produk dan harga paket final."
                        >
                            <div className="mb-4">
                                <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Harga Bundle
                                </label>
                                <input
                                    type="number"
                                    min="1"
                                    value={data.discount_value}
                                    onChange={(event) =>
                                        setData("discount_value", event.target.value)
                                    }
                                    className={inputClass}
                                />
                            </div>
                            <div className="space-y-3">
                                {data.bundle_items.map((row, index) => (
                                    <div
                                        key={`bundle-item-${index}`}
                                        className="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-[1fr_160px_48px] dark:border-slate-700 dark:bg-slate-800"
                                    >
                                        <select
                                            value={row.product_id}
                                            onChange={(event) =>
                                                updateArrayRow(
                                                    "bundle_items",
                                                    index,
                                                    "product_id",
                                                    event.target.value
                                                )
                                            }
                                            className="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                                        >
                                            <option value="">Pilih produk</option>
                                            {products.map((product) => (
                                                <option key={product.id} value={product.id}>
                                                    {product.title}
                                                </option>
                                            ))}
                                        </select>
                                        <input
                                            type="number"
                                            min="1"
                                            value={row.quantity}
                                            onChange={(event) =>
                                                updateArrayRow(
                                                    "bundle_items",
                                                    index,
                                                    "quantity",
                                                    event.target.value
                                                )
                                            }
                                            className="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                                            placeholder="Qty"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => removeRow("bundle_items", index)}
                                            className="inline-flex h-11 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 dark:border-rose-900/50 dark:bg-rose-950/40"
                                        >
                                            <IconTrash size={16} />
                                        </button>
                                    </div>
                                ))}
                                <button
                                    type="button"
                                    onClick={() =>
                                        addRow("bundle_items", {
                                            product_id: "",
                                            quantity: "1",
                                            sort_order: String(data.bundle_items.length),
                                        })
                                    }
                                    className="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200"
                                >
                                    <IconPlus size={16} />
                                    Tambah Item Bundle
                                </button>
                            </div>
                        </CardSection>
                    )}

                    {isBogoAuto && (
                        <CardSection
                            title="Beli X Gratis Y — Produk Tertentu"
                            description="Atur item pembelian (buy) dan item hadiah/diskon (get). Kosongkan kalau target = Semua Produk (pakai mode generik di bawah)."
                        >
                            {data.target_type !== "all" ? (
                                <div className="space-y-3">
                                    {data.buy_get_items.map((row, index) => (
                                        <div
                                            key={`buy-get-item-${index}`}
                                            className="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-[160px_1fr_140px_48px] dark:border-slate-700 dark:bg-slate-800"
                                        >
                                            <select
                                                value={row.role}
                                                onChange={(event) =>
                                                    updateArrayRow(
                                                        "buy_get_items",
                                                        index,
                                                        "role",
                                                        event.target.value
                                                    )
                                                }
                                                className="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                                            >
                                                <option value="buy">Beli</option>
                                                <option value="get">Gratis</option>
                                            </select>
                                            <select
                                                value={row.product_id}
                                                onChange={(event) =>
                                                    updateArrayRow(
                                                        "buy_get_items",
                                                        index,
                                                        "product_id",
                                                        event.target.value
                                                    )
                                                }
                                                className="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                                            >
                                                <option value="">Pilih produk</option>
                                                {products.map((product) => (
                                                    <option key={product.id} value={product.id}>
                                                        {product.title}
                                                    </option>
                                                ))}
                                            </select>
                                            <input
                                                type="number"
                                                min="1"
                                                value={row.quantity}
                                                onChange={(event) =>
                                                    updateArrayRow(
                                                        "buy_get_items",
                                                        index,
                                                        "quantity",
                                                        event.target.value
                                                    )
                                                }
                                                className="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                                            />
                                            <button
                                                type="button"
                                                onClick={() => removeRow("buy_get_items", index)}
                                                className="inline-flex h-11 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 dark:border-rose-900/50 dark:bg-rose-950/40"
                                            >
                                                <IconTrash size={16} />
                                            </button>
                                        </div>
                                    ))}
                                    <button
                                        type="button"
                                        onClick={() =>
                                            addRow("buy_get_items", {
                                                product_id: "",
                                                role: "buy",
                                                quantity: "1",
                                                sort_order: String(data.buy_get_items.length),
                                            })
                                        }
                                        className="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200"
                                    >
                                        <IconPlus size={16} />
                                        Tambah Item Buy/Get
                                    </button>
                                </div>
                            ) : (
                                <p className="text-sm text-slate-500 dark:text-slate-400">
                                    Target sedang "Semua Produk" — gunakan mode generik di
                                    bawah, tidak perlu pilih produk satu-satu.
                                </p>
                            )}
                        </CardSection>
                    )}

                    {(isBogoManualGeneric || (data.discount_kind === "buy_x_get_y" && data.target_type === "all")) && (
                        <CardSection
                            title="Beli X Gratis Y — Generik"
                            description="Berlaku untuk produk apa saja di keranjang (atau sesuai Target di atas), tanpa perlu memilih produk satu per satu."
                        >
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Beli Sejumlah (Qty)
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        value={data.buy_qty}
                                        onChange={(event) => setData("buy_qty", event.target.value)}
                                        className={inputClass}
                                    />
                                    <InputError message={errors.buy_qty} />
                                </div>
                                <div>
                                    <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Gratis Sejumlah (Qty)
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        value={data.get_qty}
                                        onChange={(event) => setData("get_qty", event.target.value)}
                                        className={inputClass}
                                    />
                                    <InputError message={errors.get_qty} />
                                </div>
                                <div>
                                    <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Produk Gratis{" "}
                                        <span className="text-slate-400 text-xs">
                                            (kosong = otomatis item termurah)
                                        </span>
                                    </label>
                                    <select
                                        value={data.get_product_id}
                                        onChange={(event) =>
                                            setData("get_product_id", event.target.value)
                                        }
                                        className={inputClass}
                                    >
                                        <option value="">Otomatis (item termurah)</option>
                                        {products.map((product) => (
                                            <option key={product.id} value={product.id}>
                                                {product.title}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Potongan Item Gratis (%)
                                    </label>
                                    <input
                                        type="number"
                                        min="0"
                                        max="100"
                                        value={data.get_discount_percent}
                                        onChange={(event) =>
                                            setData("get_discount_percent", event.target.value)
                                        }
                                        placeholder="100 = gratis penuh"
                                        className={inputClass}
                                    />
                                    <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        100% = gratis penuh, 50% = setengah harga, dst.
                                    </p>
                                </div>
                            </div>
                            <p className="mt-3 rounded-xl bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400">
                                Preview: Beli {data.buy_qty || "?"}{" "}
                                {data.target_type === "product"
                                    ? products.find((p) => String(p.id) === String(data.product_id))?.title ?? "produk"
                                    : data.target_type === "category"
                                    ? "produk di kategori ini"
                                    : "produk apa saja"}
                                , {Number(data.get_discount_percent) >= 100 ? "Gratis" : `Diskon ${data.get_discount_percent}%`} {data.get_qty || "?"}{" "}
                                {data.get_product_id
                                    ? products.find((p) => String(p.id) === String(data.get_product_id))?.title ?? "produk"
                                    : "(item termurah)"}
                            </p>
                        </CardSection>
                    )}

                    <CardSection
                        title="Jadwal & Catatan"
                        description="Gunakan jadwal bila promo hanya aktif pada periode tertentu."
                    >
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Mulai
                                </label>
                                <input
                                    type="datetime-local"
                                    value={data.starts_at}
                                    onChange={(event) =>
                                        setData("starts_at", event.target.value)
                                    }
                                    className={inputClass}
                                />
                            </div>
                            <div>
                                <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Berakhir
                                </label>
                                <input
                                    type="datetime-local"
                                    value={data.ends_at}
                                    onChange={(event) =>
                                        setData("ends_at", event.target.value)
                                    }
                                    className={inputClass}
                                />
                            </div>
                            <div className="md:col-span-2">
                                <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Catatan
                                </label>
                                <textarea
                                    rows="3"
                                    value={data.notes}
                                    onChange={(event) =>
                                        setData("notes", event.target.value)
                                    }
                                    className="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                />
                            </div>
                            <label className="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                <input
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(event) =>
                                        setData("is_active", event.target.checked)
                                    }
                                />
                                Aktifkan promo ini
                            </label>
                        </div>
                    </CardSection>

                    <CardSection
                        title="Preview Draft"
                        description="Simulasikan promo ini terhadap contoh produk sebelum disimpan."
                    >
                        <div className="mb-4 flex flex-wrap gap-3">
                            <button
                                type="button"
                                onClick={runPreview}
                                className="inline-flex items-center gap-2 rounded-xl border border-primary-200 bg-primary-50 px-4 py-2 text-sm font-medium text-primary-700 dark:border-primary-900/50 dark:bg-primary-950/40 dark:text-primary-300"
                            >
                                <IconChartInfographic size={16} />
                                {previewState.loading
                                    ? "Memuat preview..."
                                    : "Jalankan Preview"}
                            </button>
                        </div>

                        {previewState.data && (
                            <div className="space-y-4">
                                <div className="grid gap-3 md:grid-cols-3">
                                    <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                                        <p className="text-xs uppercase tracking-wide text-slate-500">
                                            Base subtotal
                                        </p>
                                        <p className="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                                            Rp {Number(previewState.data.summary.base_subtotal || 0).toLocaleString("id-ID")}
                                        </p>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                                        <p className="text-xs uppercase tracking-wide text-slate-500">
                                            Diskon promo
                                        </p>
                                        <p className="mt-1 text-lg font-semibold text-rose-600 dark:text-rose-300">
                                            Rp {Number((previewState.data.summary.promo_discount_total || 0) + previewVoucherDiscount).toLocaleString("id-ID")}
                                        </p>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                                        <p className="text-xs uppercase tracking-wide text-slate-500">
                                            Setelah promo
                                        </p>
                                        <p className="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                                            Rp {Number((previewState.data.summary.subtotal_after_promo || 0) - previewVoucherDiscount).toLocaleString("id-ID")}
                                        </p>
                                    </div>
                                </div>

                                {previewGroups.length > 0 && (
                                    <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                                        <h3 className="mb-3 text-sm font-semibold text-slate-900 dark:text-white">
                                            Promo yang Terapan
                                        </h3>
                                        <div className="space-y-2">
                                            {previewGroups.map((group) => (
                                                <div
                                                    key={group.key}
                                                    className="flex items-center justify-between rounded-xl bg-white px-4 py-3 text-sm dark:bg-slate-900"
                                                >
                                                    <span className="font-medium text-slate-700 dark:text-slate-200">
                                                        {group.label}
                                                    </span>
                                                    <span className="text-rose-600 dark:text-rose-300">
                                                        -Rp {Number(group.discount_total || 0).toLocaleString("id-ID")}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {previewFreeItems.length > 0 && (
                                    <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30">
                                        <h3 className="mb-2 text-sm font-semibold text-emerald-800 dark:text-emerald-300">
                                            Item Gratis (simulasi)
                                        </h3>
                                        <div className="space-y-1">
                                            {previewFreeItems.map((item, idx) => (
                                                <p key={idx} className="text-sm text-emerald-700 dark:text-emerald-400">
                                                    🎁 {item.quantity}x {item.product_title}
                                                </p>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </CardSection>

                    <div className="flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                        <Button
                            type="link"
                            href={route("promos.index")}
                            className="border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                            label="Batal"
                        />
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-500 px-5 py-2.5 font-medium text-white hover:bg-primary-600 disabled:opacity-50"
                        >
                            <IconDeviceFloppy size={18} />
                            {processing ? "Menyimpan..." : "Simpan Promo"}
                        </button>
                    </div>
                </form>
            </div>
        </>
    );
}
