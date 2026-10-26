import React, {
    useEffect,
    useMemo,
    useState,
    useCallback,
    useRef,
} from "react";
import QrisStaticDisplay from "@/Components/Dashboard/QrisStaticDisplay";
import { Head, router, usePage } from "@inertiajs/react";
import axios from "axios";
import toast from "react-hot-toast";
import POSLayout from "@/Layouts/POSLayout";
import ProductGrid from "@/Components/POS/ProductGrid";
import CartPanel from "@/Components/POS/CartPanel";
import PaymentPanel from "@/Components/POS/PaymentPanel";
import CustomerSelect from "@/Components/POS/CustomerSelect";
import NumpadModal from "@/Components/POS/NumpadModal";
import HeldTransactions, {
    HoldButton,
} from "@/Components/POS/HeldTransactions";
import useBarcodeScanner from "@/Hooks/useBarcodeScanner";
import { getProductImageUrl } from "@/Utils/imageUrl";
import { useAuthorization } from "@/Utils/authorization";
import {
    IconUser,
    IconShoppingCart,
    IconReceipt,
    IconKeyboard,
    IconBarcode,
    IconTrash,
    IconCash,
    IconCreditCard,
    IconBuildingBank,
    IconAlertTriangle,
    IconWallet,
    IconQrcode,
    IconCheck,
    IconX,
    IconExternalLink,
} from "@tabler/icons-react";

const formatPrice = (value = 0) =>
    Number(value || 0).toLocaleString("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    });

export default function Index({
    carts = [],
    carts_total = 0,
    heldCarts = [],
    activeCustomer = null,
    customers = [],
    generalVouchers = [],
    products = [],
    categories = [],
    initialPricingPreview = { items: [], summary: {} },
    paymentGateways = [],
    defaultPaymentGateway = "cash",
    bankAccounts = [],
    qrisStaticConfig = null,
    loyaltyTierOptions = [],
}) {
    const {
        auth,
        errors,
        lowStockNotifications = [],
        activeCashierShift,
    } = usePage().props;
    const { can } = useAuthorization();
    const canOpenShift = can("cashier-shifts-open");

    // State
    const [searchQuery, setSearchQuery] = useState("");
    const [selectedCategory, setSelectedCategory] = useState(null);
    const [isSearching, setIsSearching] = useState(false);
    const [addingProductId, setAddingProductId] = useState(null);
    const [removingItemId, setRemovingItemId] = useState(null);
    // Pelanggan diisi dari keranjang aktif (kalau ada) supaya tidak
    // hilang waktu kasir pindah halaman atau refresh browser.
    const [selectedCustomer, setSelectedCustomer] = useState(activeCustomer);
    const [pricingPreview, setPricingPreview] = useState(initialPricingPreview);
    const [isLoadingPricing, setIsLoadingPricing] = useState(false);
    const [discountInput, setDiscountInput] = useState("");
    const [redeemPointsInput, setRedeemPointsInput] = useState("");
    const [cashInput, setCashInput] = useState("");
    // Dual payment (Tunai + QRIS)
    const [dualPayment, setDualPayment] = useState(false);
    const [dualCashInput, setDualCashInput] = useState("");
    const [dualQrisInput, setDualQrisInput] = useState("");
    const [shippingInput, setShippingInput] = useState("");
    const [paymentMethod, setPaymentMethod] = useState(
        defaultPaymentGateway ?? "cash",
    );
    const [payLater, setPayLater] = useState(false);
    const [dueDate, setDueDate] = useState("");
    const [isSubmitting, setIsSubmitting] = useState(false);
    // Modal QRIS — tampil sebelum transaksi final disubmit
    const [showQrisModal, setShowQrisModal] = useState(false);
    const [qrisPaymentUrl, setQrisPaymentUrl] = useState(null); // untuk gateway dinamis
    const [dualStep, setDualStep] = useState(1); // 1 = terima tunai, 2 = scan QRIS
    const [mobileView, setMobileView] = useState("products"); // 'products' | 'cart'
    const [numpadOpen, setNumpadOpen] = useState(false);
    const [showShortcuts, setShowShortcuts] = useState(false);
    const [selectedBankAccount, setSelectedBankAccount] = useState(null);
    const [selectedVoucherIds, setSelectedVoucherIds] = useState([]);
    const [promoCodeInput, setPromoCodeInput] = useState("");
    const [openingCashInput, setOpeningCashInput] = useState("");
    const [shiftNotesInput, setShiftNotesInput] = useState("");
    const normalizedSelectedCategory =
        selectedCategory === null ? null : Number(selectedCategory);
    const pricingItemsByCartId = useMemo(() => {
        const items = pricingPreview?.items || [];

        return items.reduce((accumulator, item) => {
            accumulator[item.cart_id] = item;

            return accumulator;
        }, {});
    }, [pricingPreview]);

    // Ref for search input to enable keyboard focus
    const searchInputRef = useRef(null);

    // Set default payment method
    useEffect(() => {
        setPaymentMethod(defaultPaymentGateway ?? "cash");
    }, [defaultPaymentGateway]);

    useEffect(() => {
        setPricingPreview(initialPricingPreview);
    }, [initialPricingPreview]);

    useEffect(() => {
        setSelectedCustomer(activeCustomer);
    }, [activeCustomer]);

    // Sync selectedCustomer ketika activeCustomer berubah dari props
    // (misal setelah resume transaksi ditahan — customer ikut ter-restore)
    useEffect(() => {
        setSelectedCustomer(activeCustomer);
    }, [activeCustomer]);

    // Barcode scanner integration
    const handleBarcodeScan = useCallback(
        (barcode) => {
            const product = products.find(
                (p) => p.barcode?.toLowerCase() === barcode.toLowerCase(),
            );

            if (product) {
                if (product.stock > 0) {
                    handleAddToCart(product);
                    toast.success(`${product.title} ditambahkan (barcode)`);
                } else {
                    toast.error(`${product.title} stok habis`);
                }
            } else {
                toast.error(`Produk tidak ditemukan: ${barcode}`);
            }
        },
        [products],
    );

    const { isScanning } = useBarcodeScanner(handleBarcodeScan, {
        enabled: true,
        minLength: 3,
    });

    const LowStockAlerts = () => null;

    // Calculations
    const discount = useMemo(
        () => Math.max(0, Number(discountInput) || 0),
        [discountInput],
    );
    const shipping = useMemo(
        () => Math.max(0, Number(shippingInput) || 0),
        [shippingInput],
    );
    const baseSubtotal = useMemo(
        () =>
            Number(pricingPreview?.summary?.base_subtotal ?? carts_total ?? 0),
        [pricingPreview, carts_total],
    );
    const promoDiscount = useMemo(
        () => Number(pricingPreview?.summary?.promo_discount_total ?? 0),
        [pricingPreview],
    );
    const voucherDiscount = useMemo(
        () => Number(pricingPreview?.summary?.voucher_discount_total ?? 0),
        [pricingPreview],
    );
    const loyaltyDiscount = useMemo(
        () => Number(pricingPreview?.summary?.loyalty_discount_total ?? 0),
        [pricingPreview],
    );
    const subtotal = useMemo(
        () => Number(pricingPreview?.summary?.subtotal_after_promo ?? 0),
        [pricingPreview],
    );
    const payable = useMemo(
        () => Number(pricingPreview?.summary?.grand_total ?? 0),
        [pricingPreview],
    );
    const isCashPayment = !payLater && paymentMethod === "cash";
    const isDualPayment = !payLater && dualPayment && paymentMethod === "cash";
    const dualCash = useMemo(() => Math.max(0, Number(dualCashInput) || 0), [dualCashInput]);
    const dualQris = useMemo(() => Math.max(0, Number(dualQrisInput) || 0), [dualQrisInput]);
    const cash = useMemo(
        () => (isCashPayment ? (isDualPayment ? dualCash : Math.max(0, Number(cashInput) || 0)) : payable),
        [cashInput, isCashPayment, isDualPayment, dualCash, payable],
    );
    const cartCount = useMemo(
        () => carts.reduce((total, item) => total + Number(item.qty), 0),
        [carts],
    );
    const pricingDependency = useMemo(
        () => carts.map((item) => `${item.id}:${item.qty}`).join("|"),
        [carts],
    );

    useEffect(() => {
        if (carts.length === 0) {
            setPricingPreview({
                items: [],
                summary: {
                    base_subtotal: 0,
                    promo_discount_total: 0,
                    subtotal_after_promo: 0,
                    voucher_discount_total: 0,
                    loyalty_discount_total: 0,
                    manual_discount_total: 0,
                    shipping_cost: 0,
                    grand_total: 0,
                },
            });

            return;
        }

        let cancelled = false;
        setIsLoadingPricing(true);

        axios
            .post(route("transactions.pricing-preview"), {
                customer_id: selectedCustomer?.id ?? null,
                discount,
                shipping_cost: shipping,
                redeem_points: Number(redeemPointsInput || 0),
                customer_voucher_ids: selectedVoucherIds,
            })
            .then((response) => {
                if (!cancelled) {
                    setPricingPreview(
                        response.data?.data ?? initialPricingPreview,
                    );
                }
            })
            .catch(() => {
                if (!cancelled) {
                    toast.error("Gagal memuat promo aktif");
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setIsLoadingPricing(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [
        selectedCustomer?.id,
        pricingDependency,
        discount,
        shipping,
        redeemPointsInput,
        selectedVoucherIds,
    ]);

    useEffect(() => {
        if (!selectedCustomer?.is_loyalty_member) {
            setRedeemPointsInput("");
            // Jangan reset voucher — voucher umum (all/non_member_only) tetap valid untuk non-member
        }
    }, [selectedCustomer?.id, selectedCustomer?.is_loyalty_member]);

    useEffect(() => {
        const eligibleVoucherIds = new Set(
            (pricingPreview?.eligible_vouchers || []).map((voucher) =>
                String(voucher.id),
            ),
        );

        setSelectedVoucherIds((prev) => {
            const filtered = prev.filter((id) => eligibleVoucherIds.has(id));
            return filtered.length === prev.length ? prev : filtered;
        });
    }, [pricingPreview?.eligible_vouchers]);

    // Terapkan kode promo yang diketik manual di kolom "Punya Kode Promo?".
    // Bisa dipanggil lewat tombol Pakai maupun tekan Enter di input.
    // Voucher yang ketemu ditambahkan ke daftar voucher terpilih (mendukung
    // lebih dari satu voucher aktif sekaligus), tidak menggantikan yang lama.
    const applyPromoCode = useCallback(() => {
        const code = promoCodeInput.trim();
        if (!code) return;

        const match = (pricingPreview?.eligible_vouchers || []).find(
            (v) => (v.code || "").toLowerCase() === code.toLowerCase(),
        );

        if (!match) {
            toast.error(`Kode promo "${code}" tidak ditemukan atau tidak berlaku`);
            return;
        }

        const matchId = String(match.id);
        setSelectedVoucherIds((prev) =>
            prev.includes(matchId) ? prev : [...prev, matchId],
        );
        setPromoCodeInput("");
        toast.success(`Voucher "${match.name}" diterapkan`);
    }, [promoCodeInput, pricingPreview?.eligible_vouchers]);

    // Payment options
    const paymentOptions = useMemo(() => {
        const options = Array.isArray(paymentGateways)
            ? paymentGateways.filter(
                  (gateway) =>
                      gateway?.value && gateway.value.toLowerCase() !== "cash",
              )
            : [];

        return [
            {
                value: "cash",
                label: "Tunai",
                description: "Pembayaran tunai langsung di kasir.",
            },
            ...options,
        ];
    }, [paymentGateways]);

    // Auto-set cash input for non-cash payment
    useEffect(() => {
        if (!isCashPayment && payable >= 0) {
            setCashInput(String(payable));
        }
    }, [isCashPayment, payable]);

    const handleOpenShift = () => {
        router.post(route("cashier-shifts.store"), {
            opening_cash: Number(openingCashInput || 0),
            notes: shiftNotesInput,
            redirect_to: "transactions",
        });
    };

    // Handle add product to cart
    const handleAddToCart = async (product) => {
        if (!product?.id) return;

        setAddingProductId(product.id);

        router.post(
            route("transactions.addToCart"),
            {
                product_id: product.id,
                sell_price: product.sell_price,
                qty: 1,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(`${product.title} ditambahkan`);
                    setAddingProductId(null);
                },
                onError: () => {
                    toast.error("Gagal menambahkan produk");
                    setAddingProductId(null);
                },
            },
        );
    };

    // Handle update cart quantity
    const [updatingCartId, setUpdatingCartId] = useState(null);

    const handleUpdateQty = (cartId, newQty) => {
        if (newQty < 1) return;
        setUpdatingCartId(cartId);

        router.patch(
            route("transactions.updateCart", cartId),
            { qty: newQty },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setUpdatingCartId(null);
                },
                onError: (errors) => {
                    toast.error(errors?.message || "Gagal update quantity");
                    setUpdatingCartId(null);
                },
            },
        );
    };

    // Handle numpad confirm for cash input
    const handleNumpadConfirm = useCallback((value) => {
        setCashInput(String(value));
    }, []);

    // Handle hold transaction
    const [isHolding, setIsHolding] = useState(false);

    const handleHoldCart = async (label = null) => {
        if (carts.length === 0) {
            toast.error("Keranjang kosong");
            return;
        }

        setIsHolding(true);

        router.post(
            route("transactions.hold"),
            { label, customer_id: selectedCustomer?.id ?? null },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success("Transaksi ditahan");
                    setIsHolding(false);
                },
                onError: (errors) => {
                    toast.error(errors?.message || "Gagal menahan transaksi");
                    setIsHolding(false);
                },
            },
        );
    };

    // Simpan pelanggan terpilih ke keranjang aktif di server, supaya
    // tetap "ingat" walau kasir pindah halaman atau refresh browser.
    const handleSelectCustomer = (customer) => {
        setSelectedCustomer(customer);

        if (carts.length === 0) return;

        router.post(
            route("transactions.cart.set-customer"),
            { customer_id: customer?.id ?? null },
            {
                preserveScroll: true,
                preserveState: true,
                only: [],
            },
        );
    };

    // Keyboard shortcuts
    useEffect(() => {
        const handleKeyDown = (e) => {
            // Don't trigger if user is typing in an input
            if (e.target.tagName === "INPUT" || e.target.tagName === "TEXTAREA")
                return;

            switch (e.key) {
                case "/":
                case "F5":
                    e.preventDefault();
                    // Focus search input
                    if (searchInputRef.current) {
                        searchInputRef.current.focus();
                    }
                    break;
                case "F1":
                    e.preventDefault();
                    setNumpadOpen(true);
                    break;
                case "F2":
                    e.preventDefault();
                    if (carts.length > 0 && selectedCustomer)
                        handleSubmitTransaction();
                    break;
                case "F3":
                    e.preventDefault();
                    setMobileView(
                        mobileView === "products" ? "cart" : "products",
                    );
                    break;
                case "F4":
                    e.preventDefault();
                    setShowShortcuts(!showShortcuts);
                    break;
                case "Escape":
                    setNumpadOpen(false);
                    setShowShortcuts(false);
                    setSearchQuery("");
                    break;
            }
        };

        window.addEventListener("keydown", handleKeyDown);
        return () => window.removeEventListener("keydown", handleKeyDown);
    }, [carts, selectedCustomer, mobileView, showShortcuts]);

    // Handle remove from cart
    const handleRemoveFromCart = (cartId) => {
        setRemovingItemId(cartId);

        router.delete(route("transactions.destroyCart", cartId), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Item dihapus dari keranjang");
                setRemovingItemId(null);
            },
            onError: () => {
                toast.error("Gagal menghapus item");
                setRemovingItemId(null);
            },
        });
    };

    // Cek apakah metode pembayaran adalah QRIS (statis maupun dinamis)
    const isQrisPayment = ["qris_static", "doku", "flip", "midtrans", "xendit"].includes(paymentMethod);

    // Handle submit transaction — kalau QRIS, tampilkan modal dulu
    const handleSubmitTransaction = () => {
        if (carts.length === 0) {
            toast.error("Keranjang masih kosong");
            return;
        }

        if (!selectedCustomer?.id) {
            toast.error("Pilih pelanggan terlebih dahulu");
            return;
        }

        if (payLater && !dueDate) {
            toast.error("Isi tanggal jatuh tempo untuk nota barang");
            return;
        }

        if (!payLater && isCashPayment && !isDualPayment && cash < payable) {
            toast.error("Jumlah pembayaran kurang dari total");
            return;
        }

        if (isDualPayment) {
            if (dualCash <= 0 && dualQris <= 0) {
                toast.error("Isi nominal Tunai dan QRIS");
                return;
            }
            if (dualCash + dualQris < payable) {
                toast.error("Total Tunai + QRIS kurang dari total belanja");
                return;
            }
        }

        const isBankTransfer = paymentMethod === "bank_transfer";
        if (isBankTransfer && !selectedBankAccount) {
            toast.error("Pilih rekening bank tujuan");
            return;
        }

        // Kalau QRIS (statis/dinamis) → tampilkan modal dulu
        if (!payLater && isQrisPayment) {
            setQrisPaymentUrl(null);
            setShowQrisModal(true);
            return;
        }

        // Dual payment → tampilkan modal step 1 (terima tunai)
        if (isDualPayment) {
            setDualStep(1);
            setShowQrisModal(true);
            return;
        }

        // Lainnya langsung submit
        doSubmitTransaction();
    };

    // Submit transaksi ke server (dipanggil setelah konfirmasi dari modal)
    const doSubmitTransaction = async () => {
        setIsSubmitting(true);

        try {
            const response = await axios.post(route("transactions.store"), {
                customer_id: selectedCustomer.id,
                discount,
                redeem_points: Number(redeemPointsInput || 0),
                customer_voucher_ids: selectedVoucherIds,
                shipping_cost: shipping,
                grand_total: payable,
                cash: isCashPayment ? (isDualPayment ? dualCash : cash) : payable,
                change: isCashPayment && !isDualPayment ? Math.max(cash - payable, 0) : 0,
                dual_cash: isDualPayment ? dualCash : 0,
                dual_qris: isDualPayment ? dualQris : 0,
                payment_gateway: payLater
                    ? null
                    : isDualPayment
                      ? "dual_cash_qris"
                      : isCashPayment
                        ? null
                        : paymentMethod,
                bank_account_id: paymentMethod === "bank_transfer"
                    ? selectedBankAccount?.id
                    : null,
                pay_later: payLater,
                due_date: dueDate,
            }, {
                headers: { "X-Inertia": "true", "X-Inertia-Version": "1" },
                maxRedirects: 0,
                validateStatus: (s) => s < 500,
            });

            // Inertia redirect datang sebagai 409 dengan header X-Inertia-Location
            // atau 302 redirect — tangkap URL invoice dan redirect
            const redirectUrl =
                response.headers?.["x-inertia-location"] ||
                response.request?.responseURL ||
                null;

            if (redirectUrl && redirectUrl.includes("/print")) {
                toast.success("Transaksi berhasil!");
                window.location.href = redirectUrl;
            } else {
                // Fallback: pakai router.post biasa
                router.post(
                    route("transactions.store"),
                    {
                        customer_id: selectedCustomer.id,
                        discount,
                        redeem_points: Number(redeemPointsInput || 0),
                        customer_voucher_ids: selectedVoucherIds,
                        shipping_cost: shipping,
                        grand_total: payable,
                        cash: isCashPayment ? (isDualPayment ? dualCash : cash) : payable,
                        change: isCashPayment && !isDualPayment ? Math.max(cash - payable, 0) : 0,
                        dual_cash: isDualPayment ? dualCash : 0,
                        dual_qris: isDualPayment ? dualQris : 0,
                        payment_gateway: payLater
                            ? null
                            : isDualPayment
                              ? "dual_cash_qris"
                              : isCashPayment
                                ? null
                                : paymentMethod,
                        bank_account_id: paymentMethod === "bank_transfer"
                            ? selectedBankAccount?.id
                            : null,
                        pay_later: payLater,
                        due_date: dueDate,
                    },
                    {
                        onSuccess: () => toast.success("Transaksi berhasil!"),
                        onError: () => {
                            setIsSubmitting(false);
                            toast.error("Gagal menyimpan transaksi");
                        },
                    },
                );
            }
        } catch (err) {
            setIsSubmitting(false);
            toast.error("Gagal menyimpan transaksi");
        }
    };

    // Filter products including out of stock
    const allProducts = useMemo(() => {
        return products.filter((product) => {
            const matchesCategory =
                normalizedSelectedCategory === null ||
                Number(product.category_id) === normalizedSelectedCategory;
            const matchesSearch =
                !searchQuery ||
                product.title
                    .toLowerCase()
                    .includes(searchQuery.toLowerCase()) ||
                product.barcode
                    ?.toLowerCase()
                    .includes(searchQuery.toLowerCase());
            return matchesCategory && matchesSearch;
        });
    }, [products, normalizedSelectedCategory, searchQuery]);

    if (!activeCashierShift) {
        return (
            <>
                <Head title="Buka Shift Kasir" />

                <div className="mx-auto flex min-h-[calc(100vh-8rem)] max-w-3xl items-center justify-center px-4 py-10">
                    <div className="w-full rounded-3xl border border-slate-200 bg-white p-8 shadow-xl shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                        <div className="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                            <IconWallet size={28} />
                        </div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                            Shift kasir belum dibuka
                        </h1>
                        <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">
                            Buka shift terlebih dulu untuk mengaktifkan
                            transaksi, keranjang, dan cash closing.
                        </p>

                        <div className="mt-6 grid gap-4 md:grid-cols-2">
                            <div>
                                <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Modal Awal
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    value={openingCashInput}
                                    onChange={(event) =>
                                        setOpeningCashInput(event.target.value)
                                    }
                                    className="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                    placeholder="0"
                                />
                                {errors?.opening_cash && (
                                    <p className="mt-2 text-xs text-rose-500">
                                        {errors.opening_cash}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Catatan
                                </label>
                                <input
                                    type="text"
                                    value={shiftNotesInput}
                                    onChange={(event) =>
                                        setShiftNotesInput(event.target.value)
                                    }
                                    className="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                    placeholder="Opsional"
                                />
                            </div>
                        </div>

                        <div className="mt-6 flex flex-col gap-3 sm:flex-row">
                            {canOpenShift && (
                                <button
                                    type="button"
                                    onClick={handleOpenShift}
                                    className="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-500 px-5 py-3 text-sm font-medium text-white transition-colors hover:bg-primary-600"
                                >
                                    <IconWallet size={18} />
                                    <span>Buka Shift Sekarang</span>
                                </button>
                            )}
                            <button
                                type="button"
                                onClick={() =>
                                    router.visit(route("cashier-shifts.index"))
                                }
                                className="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                            >
                                <span>Lihat Histori Shift</span>
                            </button>
                        </div>
                    </div>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Transaksi" />

            <div className="h-[calc(100vh-4rem)] flex flex-col lg:flex-row">
                {/* Mobile Tab Switcher */}
                <div className="lg:hidden flex border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                    <button
                        onClick={() => setMobileView("products")}
                        className={`flex-1 flex items-center justify-center gap-2 py-3 text-sm font-medium transition-colors ${
                            mobileView === "products"
                                ? "text-primary-600 border-b-2 border-primary-500"
                                : "text-slate-500"
                        }`}
                    >
                        <IconShoppingCart size={18} />
                        <span>Produk</span>
                    </button>
                    <button
                        onClick={() => setMobileView("cart")}
                        className={`flex-1 flex items-center justify-center gap-2 py-3 text-sm font-medium transition-colors relative ${
                            mobileView === "cart"
                                ? "text-primary-600 border-b-2 border-primary-500"
                                : "text-slate-500"
                        }`}
                    >
                        <IconReceipt size={18} />
                        <span className="relative inline-flex items-center gap-1">
                            Keranjang
                            {cartCount > 0 && (
                                <span className="inline-flex items-center justify-center px-1.5 min-w-[20px] h-5 text-[11px] font-bold bg-primary-500 text-white rounded-full">
                                    {cartCount}
                                </span>
                            )}
                        </span>
                    </button>
                </div>

                {/* Left Panel - Products */}
                <div
                    className={`flex-1 bg-slate-100 dark:bg-slate-950 overflow-hidden ${
                        mobileView !== "products"
                            ? "hidden lg:flex lg:flex-col"
                            : "flex flex-col"
                    }`}
                >
                    <ProductGrid
                        products={allProducts}
                        categories={categories}
                        selectedCategory={selectedCategory}
                        onCategoryChange={(categoryId) =>
                            setSelectedCategory(
                                categoryId === null ? null : Number(categoryId),
                            )
                        }
                        searchQuery={searchQuery}
                        onSearchChange={setSearchQuery}
                        isSearching={isSearching}
                        onAddToCart={handleAddToCart}
                        addingProductId={addingProductId}
                        searchInputRef={searchInputRef}
                    />
                </div>

                {/* Right Panel - Cart & Payment */}
                <div
                    className={`w-full lg:w-[420px] xl:w-[480px] flex flex-col bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 min-h-0 overflow-hidden ${
                        mobileView !== "cart" ? "hidden lg:flex" : "flex"
                    }`}
                    style={{ height: "calc(100vh - 4rem)" }}
                >
                    {/* Customer Select - Fixed */}
                    <div className="p-3 border-b border-slate-200 dark:border-slate-800 flex-shrink-0">
                        <CustomerSelect
                            customers={customers}
                            selected={selectedCustomer}
                            onSelect={handleSelectCustomer}
                            placeholder="Pilih pelanggan..."
                            error={errors?.customer_id}
                            label="Pelanggan"
                            tierOptions={loyaltyTierOptions}
                        />
                    </div>

                    {/* Held Transactions & Alerts */}
                    {heldCarts.length > 0 && (
                        <div className="p-3 border-b border-slate-200 dark:border-slate-800">
                            <HeldTransactions
                                heldCarts={heldCarts}
                                hasActiveCart={carts.length > 0}
                            />
                        </div>
                    )}

                    {/* Cart Items - Scrollable */}
                    <div className="flex-1 overflow-y-auto min-h-0">
                        {/* Hold Button - at top of cart section */}
                        {carts.length > 0 && (
                            <div className="p-3 border-b border-slate-200 dark:border-slate-800">
                                <HoldButton
                                    hasItems={carts.length > 0}
                                    onHold={handleHoldCart}
                                    isHolding={isHolding}
                                />
                            </div>
                        )}

                        <div className="p-3 border-b border-slate-200 dark:border-slate-800">
                            <div className="flex items-center justify-between mb-3">
                                <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                                    <IconShoppingCart size={16} />
                                    Keranjang
                                </h3>
                                {carts.length > 0 && (
                                    <span className="px-2.5 py-0.5 text-xs font-bold bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300 rounded-full whitespace-nowrap">
                                        {cartCount} item
                                    </span>
                                )}
                            </div>

                            {carts.length > 0 ? (
                                <div className="space-y-2 max-h-[200px] overflow-y-auto pr-1">
                                    {carts.map((item) =>
                                        (() => {
                                            const pricingItem =
                                                pricingItemsByCartId[item.id];
                                            const baseLineTotal = Number(
                                                pricingItem?.line_base_total ??
                                                    item.price ??
                                                    0,
                                            );
                                            const effectiveLineTotal = Number(
                                                pricingItem?.line_total ??
                                                    item.price ??
                                                    0,
                                            );
                                            const effectiveUnitPrice = Number(
                                                pricingItem?.effective_unit_price ??
                                                    item.product?.sell_price ??
                                                    0,
                                            );
                                            const baseUnitPrice = Number(
                                                pricingItem?.base_unit_price ??
                                                    item.product?.sell_price ??
                                                    0,
                                            );
                                            const pricingRule =
                                                pricingItem?.promo;

                                            return (
                                                <div
                                                    key={item.id}
                                                    className="flex items-center gap-2 p-2 rounded-lg bg-slate-50 dark:bg-slate-800/50 group"
                                                >
                                                    <div className="w-10 h-10 rounded-lg bg-slate-200 dark:bg-slate-700 overflow-hidden flex-shrink-0">
                                                        {item.product?.image ? (
                                                            <img
                                                                src={getProductImageUrl(
                                                                    item.product
                                                                        .image,
                                                                )}
                                                                alt={
                                                                    item.product
                                                                        .title
                                                                }
                                                                className="w-full h-full object-cover"
                                                            />
                                                        ) : (
                                                            <div className="w-full h-full flex items-center justify-center">
                                                                <IconShoppingCart
                                                                    size={14}
                                                                    className="text-slate-400"
                                                                />
                                                            </div>
                                                        )}
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <p className="text-xs font-medium text-slate-700 dark:text-slate-300 truncate">
                                                            {item.product
                                                                ?.title ||
                                                                "Produk"}
                                                        </p>
                                                        <div className="text-xs text-slate-500">
                                                            {pricingRule &&
                                                                effectiveUnitPrice <
                                                                    baseUnitPrice && (
                                                                    <p className="line-through text-slate-400">
                                                                        {formatPrice(
                                                                            baseUnitPrice,
                                                                        )}{" "}
                                                                        ×{" "}
                                                                        {
                                                                            item.qty
                                                                        }
                                                                    </p>
                                                                )}
                                                            <p>
                                                                {formatPrice(
                                                                    effectiveUnitPrice,
                                                                )}{" "}
                                                                × {item.qty}
                                                            </p>
                                                            {pricingRule && (
                                                                <p className="mt-0.5 text-[11px] font-medium text-rose-500">
                                                                    {
                                                                        pricingRule.name
                                                                    }
                                                                </p>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center gap-1">
                                                        <button
                                                            onClick={() =>
                                                                handleUpdateQty(
                                                                    item.id,
                                                                    Math.max(
                                                                        1,
                                                                        item.qty -
                                                                            1,
                                                                    ),
                                                                )
                                                            }
                                                            disabled={
                                                                item.qty <= 1
                                                            }
                                                            className="w-6 h-6 rounded flex items-center justify-center bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-300 disabled:opacity-50 text-xs"
                                                        >
                                                            -
                                                        </button>
                                                        <span className="w-6 text-center text-xs font-medium">
                                                            {item.qty}
                                                        </span>
                                                        <button
                                                            onClick={() =>
                                                                handleUpdateQty(
                                                                    item.id,
                                                                    item.qty +
                                                                        1,
                                                                )
                                                            }
                                                            className="w-6 h-6 rounded flex items-center justify-center bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-300 text-xs"
                                                        >
                                                            +
                                                        </button>
                                                        <button
                                                            onClick={() =>
                                                                handleRemoveFromCart(
                                                                    item.id,
                                                                )
                                                            }
                                                            className="w-6 h-6 rounded flex items-center justify-center text-slate-400 hover:text-danger-500 hover:bg-danger-50 dark:hover:bg-danger-950/50 ml-1"
                                                        >
                                                            <IconTrash
                                                                size={12}
                                                            />
                                                        </button>
                                                    </div>
                                                    <p className="text-xs font-semibold text-primary-600 dark:text-primary-400 w-16 text-right">
                                                        {formatPrice(
                                                            effectiveLineTotal,
                                                        )}
                                                    </p>
                                                </div>
                                            );
                                        })(),
                                    )}
                                </div>
                            ) : (
                                <div className="py-6 text-center">
                                    <IconShoppingCart
                                        size={32}
                                        className="mx-auto text-slate-300 dark:text-slate-600 mb-2"
                                    />
                                    <p className="text-sm text-slate-400">
                                        Keranjang kosong
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Payment Details - Scrollable */}
                        <div className="p-3 space-y-4">
                            {/* Pay later toggle */}
                            <div className="flex items-center justify-between p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                                <div>
                                    <p className="text-sm font-semibold text-slate-800 dark:text-white">
                                        Bayar Belakangan (Nota Barang)
                                    </p>
                                    <p className="text-xs text-slate-500">
                                        Tidak perlu bayar sekarang, catat
                                        sebagai piutang.
                                    </p>
                                </div>
                                <label className="inline-flex items-center cursor-pointer">
                                    <input
                                        type="checkbox"
                                        className="sr-only"
                                        checked={payLater}
                                        onChange={(e) => {
                                            setPayLater(e.target.checked);
                                            if (e.target.checked) {
                                                setSelectedBankAccount(null);
                                                setPaymentMethod("cash");
                                            }
                                        }}
                                    />
                                    <span
                                        className={`w-11 h-6 flex items-center bg-slate-300 rounded-full p-1 transition ${
                                            payLater ? "bg-primary-500" : ""
                                        }`}
                                    >
                                        <span
                                            className={`bg-white w-4 h-4 rounded-full shadow transform transition ${
                                                payLater ? "translate-x-5" : ""
                                            }`}
                                        />
                                    </span>
                                </label>
                            </div>

                            {payLater && (
                                <div>
                                    <label className="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-2">
                                        Tanggal Jatuh Tempo
                                    </label>
                                    <input
                                        type="date"
                                        value={dueDate}
                                        onChange={(e) =>
                                            setDueDate(e.target.value)
                                        }
                                        className="w-full h-11 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                    />
                                </div>
                            )}

                            {/* Payment Method Selection */}
                            <div>
                                <label className="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-2">
                                    Metode Pembayaran
                                </label>
                                <div className="grid grid-cols-2 gap-2">
                                    {paymentOptions.map((method) => (
                                        <button
                                            key={method.value}
                                            onClick={() => {
                                                if (payLater) return;
                                                setPaymentMethod(method.value);
                                                setDualPayment(false);
                                                setDualCashInput("");
                                                setDualQrisInput("");
                                                setCashInput("");
                                            }}
                                            disabled={payLater}
                                            className={`p-3 rounded-xl border-2 transition-all flex items-center gap-2 ${
                                                paymentMethod ===
                                                    method.value && !payLater && !dualPayment
                                                    ? "border-primary-500 bg-primary-50 dark:bg-primary-950/30"
                                                    : "border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600"
                                            } ${payLater ? "opacity-50 cursor-not-allowed" : ""}`}
                                        >
                                            <div
                                                className={`w-8 h-8 rounded-lg flex items-center justify-center ${
                                                    paymentMethod ===
                                                        method.value &&
                                                    !payLater && !dualPayment
                                                        ? "bg-primary-500 text-white"
                                                        : "bg-slate-100 dark:bg-slate-800 text-slate-500"
                                                }`}
                                            >
                                                {method.value === "cash" ? (
                                                    <IconCash size={16} />
                                                ) : method.value ===
                                                  "bank_transfer" ? (
                                                    <IconBuildingBank
                                                        size={16}
                                                    />
                                                ) : (
                                                    <IconCreditCard size={16} />
                                                )}
                                            </div>
                                            <div className="text-left">
                                                <p
                                                    className={`text-sm font-semibold ${
                                                        paymentMethod ===
                                                        method.value && !dualPayment
                                                            ? "text-primary-700 dark:text-primary-300"
                                                            : "text-slate-700 dark:text-slate-300"
                                                    }`}
                                                >
                                                    {method.label}
                                                </p>
                                            </div>
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {/* Bank Selector - Only for bank_transfer */}
                            {paymentMethod === "bank_transfer" &&
                                bankAccounts.length > 0 &&
                                !payLater && (
                                    <div>
                                        <label className="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-2">
                                            Rekening Tujuan
                                        </label>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            {bankAccounts.map((bank) => {
                                                const isActive =
                                                    selectedBankAccount?.id ===
                                                    bank.id;
                                                return (
                                                    <button
                                                        key={bank.id}
                                                        onClick={() =>
                                                            setSelectedBankAccount(
                                                                bank,
                                                            )
                                                        }
                                                        className={`p-3 rounded-xl border-2 transition-colors flex items-center gap-3 text-left ${
                                                            isActive
                                                                ? "border-primary-500 bg-primary-50 dark:bg-primary-950/30"
                                                                : "border-slate-200 dark:border-slate-700 hover:border-primary-200 dark:hover:border-primary-800"
                                                        }`}
                                                    >
                                                        <div className="w-10 h-10 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 flex items-center justify-center overflow-hidden">
                                                            {bank.logo_url ? (
                                                                <img
                                                                    src={
                                                                        bank.logo_url
                                                                    }
                                                                    alt={
                                                                        bank.bank_name
                                                                    }
                                                                    className="max-w-full max-h-full object-contain"
                                                                />
                                                            ) : (
                                                                <IconBuildingBank
                                                                    size={18}
                                                                    className="text-slate-500"
                                                                />
                                                            )}
                                                        </div>
                                                        <div className="flex-1">
                                                            <p className="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                                                {bank.bank_name}
                                                            </p>
                                                            <p className="text-xs text-slate-600 dark:text-slate-400">
                                                                {
                                                                    bank.account_number
                                                                }
                                                            </p>
                                                            <p className="text-[11px] text-slate-500 dark:text-slate-500">
                                                                a.n.{" "}
                                                                {
                                                                    bank.account_name
                                                                }
                                                            </p>
                                                        </div>
                                                        {isActive && (
                                                            <span className="text-[11px] font-semibold text-primary-600">
                                                                Dipilih
                                                            </span>
                                                        )}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}

                            {/* Quick Amounts - Only for QRIS */}
                            {paymentMethod === "qris_static" && !payLater && (
                                <QrisStaticDisplay
                                    qrisConfig={qrisStaticConfig}
                                    totalAmount={payable}
                                />
                            )}

                            {/* Tunai / Tunai+QRIS section */}
                            {paymentMethod === "cash" && (
                                <div className="space-y-3">
                                    <button
                                        onClick={() => {
                                            setDualPayment(!dualPayment);
                                            setDualCashInput("");
                                            setDualQrisInput("");
                                            setCashInput("");
                                        }}
                                        className={`w-full p-3 rounded-xl border-2 transition-all text-left ${
                                            dualPayment
                                                ? "border-primary-500 bg-primary-50 dark:bg-primary-950/30"
                                                : "border-slate-200 dark:border-slate-700 hover:border-slate-300"
                                        }`}
                                    >
                                        <p className={`text-sm font-semibold ${dualPayment ? "text-primary-700 dark:text-primary-300" : "text-slate-700 dark:text-slate-300"}`}>
                                            Tunai + QRIS
                                        </p>
                                        <p className="text-xs text-slate-400">Bayar split dua metode</p>
                                    </button>

                                    {dualPayment ? (
                                        <div className="space-y-3">
                                            <div>
                                                <label className="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Bayar Tunai
                                                </label>
                                                <input
                                                    type="text"
                                                    inputMode="numeric"
                                                    value={dualCashInput}
                                                    onChange={(e) => {
                                                        const v = e.target.value.replace(/[^0-9]/g, "");
                                                        setDualCashInput(v);
                                                        const sisa = Math.max(0, payable - (Number(v) || 0));
                                                        setDualQrisInput(String(sisa));
                                                    }}
                                                    placeholder="Nominal tunai"
                                                    className="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                                                    Bayar QRIS
                                                </label>
                                                <input
                                                    type="text"
                                                    inputMode="numeric"
                                                    value={dualQrisInput}
                                                    onChange={(e) => {
                                                        const v = e.target.value.replace(/[^0-9]/g, "");
                                                        setDualQrisInput(v);
                                                        const sisa = Math.max(0, payable - (Number(v) || 0));
                                                        setDualCashInput(String(sisa));
                                                    }}
                                                    placeholder="Nominal QRIS"
                                                    className="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                                />
                                            </div>
                                            <div className="rounded-xl bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800 p-3 space-y-1 text-xs">
                                                <div className="flex justify-between text-slate-600 dark:text-slate-400">
                                                    <span>Total belanja</span>
                                                    <span className="font-semibold">{formatPrice(payable)}</span>
                                                </div>
                                                <div className="flex justify-between text-slate-600 dark:text-slate-400">
                                                    <span>Tunai</span>
                                                    <span>{formatPrice(dualCash)}</span>
                                                </div>
                                                <div className="flex justify-between text-slate-600 dark:text-slate-400">
                                                    <span>QRIS</span>
                                                    <span>{formatPrice(dualQris)}</span>
                                                </div>
                                                <div className={`flex justify-between font-bold border-t pt-1 ${dualCash + dualQris >= payable ? "text-success-600 dark:text-success-400" : "text-danger-600 dark:text-danger-400"}`}>
                                                    <span>{dualCash + dualQris >= payable ? "Cukup" : "Kurang"}</span>
                                                    <span>{dualCash + dualQris >= payable ? `Kembalian ${formatPrice(dualCash + dualQris - payable)}` : `Kurang ${formatPrice(payable - dualCash - dualQris)}`}</span>
                                                </div>
                                            </div>
                                        </div>
                                    ) : (
                                        <div>
                                            <label className="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-2">
                                                Nominal Cepat
                                            </label>
                                            <div className="grid grid-cols-4 gap-2">
                                                {[10000, 20000, 50000, 100000].map((amt) => (
                                                    <button
                                                        key={amt}
                                                        onClick={() => setCashInput(String(amt))}
                                                        className={`py-2 px-1 rounded-lg text-xs font-semibold transition-all ${
                                                            Number(cashInput) === amt
                                                                ? "bg-primary-500 text-white"
                                                                : "bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200"
                                                        }`}
                                                    >
                                                        {formatPrice(amt)}
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Discount Input */}
                            {promoDiscount > 0 && (
                                <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <p className="text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                                                Promo otomatis aktif
                                            </p>
                                            <p className="text-xs text-emerald-600/80 dark:text-emerald-400/80">
                                                Harga item sudah disesuaikan
                                                berdasarkan rule promo yang
                                                berlaku.
                                            </p>
                                        </div>
                                        <span className="text-sm font-bold text-emerald-700 dark:text-emerald-300">
                                            -{formatPrice(promoDiscount)}
                                        </span>
                                    </div>
                                </div>
                            )}

                            {selectedCustomer?.is_loyalty_member && (
                                <div className="rounded-xl border border-primary-200 bg-primary-50 p-3 dark:border-primary-900/40 dark:bg-primary-950/20">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <p className="text-sm font-semibold text-primary-700 dark:text-primary-300">
                                                Loyalty Member
                                            </p>
                                            <p className="text-xs text-primary-600/80 dark:text-primary-400/80">
                                                Tier{" "}
                                                {selectedCustomer.loyalty_tier}{" "}
                                                | saldo{" "}
                                                {pricingPreview?.summary
                                                    ?.available_loyalty_points ??
                                                    0}{" "}
                                                poin
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {selectedCustomer?.is_loyalty_member && (
                                <div>
                                    <label className="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-2">
                                        Redeem Poin
                                    </label>
                                    <input
                                        type="text"
                                        inputMode="numeric"
                                        value={redeemPointsInput}
                                        onChange={(e) =>
                                            setRedeemPointsInput(
                                                e.target.value.replace(
                                                    /[^\d]/g,
                                                    "",
                                                ),
                                            )
                                        }
                                        placeholder={`Maks ${
                                            pricingPreview?.summary
                                                ?.available_loyalty_points ?? 0
                                        } poin`}
                                        className="w-full h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                    />
                                </div>
                            )}

                            {/* Kode Promo — ketik manual lalu tekan Enter atau klik Pakai */}
                            {(pricingPreview?.eligible_vouchers || []).length > 0 && (
                                <div>
                                    <label className="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">
                                        Punya Kode Promo?
                                    </label>
                                    <div className="flex gap-2">
                                        <input
                                            type="text"
                                            value={promoCodeInput}
                                            onChange={(e) =>
                                                setPromoCodeInput(e.target.value.toUpperCase())
                                            }
                                            onKeyDown={(e) => {
                                                if (e.key === "Enter") {
                                                    e.preventDefault();
                                                    applyPromoCode();
                                                }
                                            }}
                                            placeholder="MASUKKAN KODE PROMO"
                                            className="flex-1 h-10 px-3 rounded-xl border border-slate-200 dark:border-slate-700
                                                bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 text-sm font-mono
                                                tracking-wide focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500
                                                transition-all"
                                        />
                                        <button
                                            type="button"
                                            onClick={applyPromoCode}
                                            disabled={!promoCodeInput.trim()}
                                            className="px-4 h-10 rounded-xl text-sm font-semibold bg-primary-500 text-white
                                                hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed
                                                transition-colors shrink-0"
                                        >
                                            Pakai
                                        </button>
                                    </div>
                                </div>
                            )}

                            {/* Voucher — personal & umum, semua jenis diskon (termasuk BOGO).
                                Tetap tampil walau belum pilih pelanggan, karena voucher umum
                                non_member_only/all tetap berlaku untuk transaksi walk-in.
                                Bisa pilih lebih dari satu voucher sekaligus. */}
                            {(pricingPreview?.eligible_vouchers || []).length > 0 && (
                                <div>
                                    <label className="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">
                                        Voucher Tersedia
                                        {selectedCustomer?.is_loyalty_member && (
                                            <span className="ml-1.5 px-1.5 py-0.5 rounded text-[10px] bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Member</span>
                                        )}
                                    </label>
                                    <div className="space-y-1.5">
                                        {/* Opsi tanpa voucher — bersihkan semua voucher yang dipilih */}
                                        <button
                                            type="button"
                                            onClick={() => setSelectedVoucherIds([])}
                                            className={`w-full flex items-center justify-between px-3 py-2 rounded-xl border-2 transition-all text-left ${
                                                selectedVoucherIds.length === 0
                                                    ? "border-slate-400 bg-slate-50 dark:bg-slate-800"
                                                    : "border-slate-200 dark:border-slate-700 hover:border-slate-300"
                                            }`}
                                        >
                                            <span className="text-sm text-slate-500 dark:text-slate-400">Tanpa voucher</span>
                                        </button>

                                        {(pricingPreview?.eligible_vouchers || []).map((v) => {
                                            const isSelected = selectedVoucherIds.includes(String(v.id));
                                            const isPersonal = (v.voucher_type ?? "personal") === "personal";
                                            const isBogo = v.discount_kind === "buy_x_get_y";
                                            const isQtyBreak = v.discount_kind === "qty_break";
                                            const typeBadge = isPersonal
                                                ? { label: "Personal", cls: "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400" }
                                                : v.voucher_type === "member_only"
                                                ? { label: "Member", cls: "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400" }
                                                : v.voucher_type === "non_member_only"
                                                ? { label: "Non-Member", cls: "bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400" }
                                                : { label: "Umum", cls: "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400" };

                                            const discountLabel = isBogo
                                                ? (() => {
                                                      const buyQty = v.buy_qty ?? 1;
                                                      const getQty = v.get_qty ?? 1;
                                                      const pct = v.get_discount_percent ?? 100;
                                                      const suffix = pct >= 100 ? "Gratis" : `Diskon ${pct}%`;
                                                      return `Beli ${buyQty} ${suffix} ${getQty}`;
                                                  })()
                                                : isQtyBreak
                                                ? "Grosir Bertingkat"
                                                : v.discount_type === "percentage"
                                                ? `${v.discount_value}%`
                                                : `-Rp${Number(v.discount_value).toLocaleString("id-ID")}`;

                                            const scopeLabel = (isBogo || isQtyBreak)
                                                ? (v.target_type === "product"
                                                      ? v.product_name || "Produk tertentu"
                                                      : v.target_type === "category"
                                                      ? v.category_name || "Kategori tertentu"
                                                      : "Semua produk")
                                                : null;

                                            const toggleVoucher = () =>
                                                setSelectedVoucherIds((prev) =>
                                                    isSelected
                                                        ? prev.filter((id) => id !== String(v.id))
                                                        : [...prev, String(v.id)],
                                                );

                                            return (
                                                <button
                                                    key={v.id}
                                                    type="button"
                                                    onClick={toggleVoucher}
                                                    className={`w-full flex items-center justify-between px-3 py-2.5 rounded-xl border-2 transition-all text-left ${
                                                        isSelected
                                                            ? "border-primary-500 bg-primary-50 dark:bg-primary-950/30"
                                                            : "border-slate-200 dark:border-slate-700 hover:border-primary-300 dark:hover:border-primary-800"
                                                    }`}
                                                >
                                                    <div className="flex items-center gap-2 min-w-0">
                                                        <div
                                                            className={`w-5 h-5 shrink-0 rounded-md border-2 flex items-center justify-center ${
                                                                isSelected
                                                                    ? "border-primary-500 bg-primary-500"
                                                                    : "border-slate-300 dark:border-slate-600"
                                                            }`}
                                                        >
                                                            {isSelected && (
                                                                <IconCheck size={13} className="text-white" />
                                                            )}
                                                        </div>
                                                        <div className="min-w-0">
                                                            <div className="flex items-center gap-1.5 mb-0.5">
                                                                <p className={`text-sm font-semibold truncate ${isSelected ? "text-primary-700 dark:text-primary-300" : "text-slate-800 dark:text-white"}`}>
                                                                    {v.name}
                                                                </p>
                                                                <span className={`shrink-0 text-[10px] font-medium px-1.5 py-0.5 rounded ${typeBadge.cls}`}>
                                                                    {typeBadge.label}
                                                                </span>
                                                                {isBogo && (
                                                                    <span className="shrink-0 text-[10px] font-medium px-1.5 py-0.5 rounded bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400">
                                                                        BOGO
                                                                    </span>
                                                                )}
                                                            </div>
                                                            <p className="text-xs text-slate-400 truncate">
                                                                {v.code}
                                                                {scopeLabel && ` · ${scopeLabel}`}
                                                                {v.minimum_order > 0 && ` · min Rp${Number(v.minimum_order).toLocaleString("id-ID")}`}
                                                                {v.ends_at && ` · s/d ${new Date(v.ends_at).toLocaleDateString("id-ID")}`}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <span className={`text-xs font-bold shrink-0 ml-2 text-right ${isSelected ? "text-primary-600 dark:text-primary-400" : "text-emerald-600 dark:text-emerald-400"}`}>
                                                        {discountLabel}
                                                    </span>
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}

                            <div>
                                <label className="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-2">
                                    Diskon Manual (Rp)
                                </label>
                                <div className="relative">
                                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">
                                        Rp
                                    </span>
                                    <input
                                        type="text"
                                        inputMode="numeric"
                                        value={discountInput}
                                        onChange={(e) =>
                                            setDiscountInput(
                                                e.target.value.replace(
                                                    /[^\d]/g,
                                                    "",
                                                ),
                                            )
                                        }
                                        placeholder="0"
                                        className="w-full h-10 pl-10 pr-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                    />
                                </div>
                            </div>

                            {/* Shipping Cost Input */}
                            <div>
                                <label className="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-2">
                                    Ongkos Kirim (Rp)
                                </label>
                                <div className="relative">
                                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">
                                        Rp
                                    </span>
                                    <input
                                        type="text"
                                        inputMode="numeric"
                                        value={shippingInput}
                                        onChange={(e) =>
                                            setShippingInput(
                                                e.target.value.replace(
                                                    /[^\d]/g,
                                                    "",
                                                ),
                                            )
                                        }
                                        placeholder="0"
                                        className="w-full h-10 pl-10 pr-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                    />
                                </div>
                                {/* Quick Shipping Amounts */}
                                <div className="grid grid-cols-4 gap-2 mt-2">
                                    {[10000, 15000, 20000, 25000].map((amt) => (
                                        <button
                                            key={amt}
                                            type="button"
                                            onClick={() =>
                                                setShippingInput(String(amt))
                                            }
                                            className={`py-1.5 px-1 rounded-lg text-xs font-medium transition-all ${
                                                Number(shippingInput) === amt
                                                    ? "bg-primary-500 text-white"
                                                    : "bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200"
                                            }`}
                                        >
                                            {formatPrice(amt)}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {/* Input Tunai - Hanya untuk tunai, disembunyikan saat dual payment */}
                            {paymentMethod === "cash" && !dualPayment && (
                                <div>
                                    <label className="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-2">
                                        Jumlah Bayar (Rp)
                                    </label>
                                    <div className="relative">
                                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">
                                            Rp
                                        </span>
                                        <input
                                            type="text"
                                            inputMode="numeric"
                                            value={cashInput}
                                            onChange={(e) =>
                                                setCashInput(
                                                    e.target.value.replace(
                                                        /[^\d]/g,
                                                        "",
                                                    ),
                                                )
                                            }
                                            placeholder="0"
                                            className="w-full h-10 pl-10 pr-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-base font-semibold focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                        />
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Summary & Submit - Fixed at bottom */}
                    <div className="flex-shrink-0 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/80 p-3">
                        {/* Summary Row */}
                        <div className="flex justify-between items-center mb-2 text-sm">
                            <span className="text-slate-500">
                                Subtotal Dasar
                            </span>
                            <span className="font-medium">
                                {formatPrice(baseSubtotal)}
                            </span>
                        </div>
                        {promoDiscount > 0 && (
                            <div className="flex justify-between items-center mb-2 text-sm">
                                <span className="text-slate-500">
                                    Promo Otomatis
                                </span>
                                <span className="text-emerald-600">
                                    -{formatPrice(promoDiscount)}
                                </span>
                            </div>
                        )}
                        {(pricingPreview?.applied_groups || []).length > 0 && (
                            <div className="mb-3 rounded-xl border border-slate-200 bg-white/70 p-2 dark:border-slate-700 dark:bg-slate-900/60">
                                <div className="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    Grup Promo Aktif
                                </div>
                                <div className="space-y-1.5">
                                    {(pricingPreview?.applied_groups || []).map(
                                        (group) => (
                                            <div
                                                key={group.key}
                                                className="flex items-center justify-between text-xs"
                                            >
                                                <span className="truncate pr-3 text-slate-600 dark:text-slate-300">
                                                    {group.label}
                                                </span>
                                                <span className="font-medium text-emerald-600">
                                                    -
                                                    {formatPrice(
                                                        group.discount_total,
                                                    )}
                                                </span>
                                            </div>
                                        ),
                                    )}
                                </div>
                            </div>
                        )}
                        {voucherDiscount > 0 && (
                            <div className="flex justify-between items-center mb-2 text-sm">
                                <span className="text-slate-500">Voucher</span>
                                <span className="text-primary-600">
                                    -{formatPrice(voucherDiscount)}
                                </span>
                            </div>
                        )}
                        {loyaltyDiscount > 0 && (
                            <div className="flex justify-between items-center mb-2 text-sm">
                                <span className="text-slate-500">
                                    Redeem Poin
                                </span>
                                <span className="text-primary-600">
                                    -{formatPrice(loyaltyDiscount)}
                                </span>
                            </div>
                        )}
                        {discount > 0 && (
                            <div className="flex justify-between items-center mb-2 text-sm">
                                <span className="text-slate-500">
                                    Diskon Manual
                                </span>
                                <span className="text-danger-500">
                                    -{formatPrice(discount)}
                                </span>
                            </div>
                        )}
                        {shipping > 0 && (
                            <div className="flex justify-between items-center mb-2 text-sm">
                                <span className="text-slate-500">Ongkir</span>
                                <span className="font-medium">
                                    +{formatPrice(shipping)}
                                </span>
                            </div>
                        )}
                        <div className="flex justify-between items-center mb-3">
                            <span className="font-semibold text-slate-800 dark:text-white">
                                Total
                            </span>
                            <span className="text-xl font-bold text-primary-600 dark:text-primary-400">
                                {formatPrice(payable)}
                            </span>
                        </div>

                        {paymentMethod === "cash" &&
                            !payLater &&
                            cash >= payable &&
                            payable > 0 && (
                                <div className="flex justify-between items-center mb-3 p-2 rounded-lg bg-success-50 dark:bg-success-950/30">
                                    <span className="text-sm text-success-700 dark:text-success-400">
                                        Kembalian
                                    </span>
                                    <span className="font-bold text-success-600">
                                        {formatPrice(cash - payable)}
                                    </span>
                                </div>
                            )}

                        {/* Submit Button - Always visible */}
                        <button
                            onClick={handleSubmitTransaction}
                            disabled={
                                !carts.length ||
                                !selectedCustomer ||
                                (!payLater &&
                                    paymentMethod === "cash" &&
                                    !isDualPayment &&
                                    cash < payable) ||
                                (!payLater &&
                                    isDualPayment &&
                                    dualCash + dualQris < payable) ||
                                isLoadingPricing ||
                                isSubmitting
                            }
                            className={`w-full h-12 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 transition-all ${
                                carts.length &&
                                selectedCustomer &&
                                (paymentMethod !== "cash" || (isDualPayment ? dualCash + dualQris >= payable : cash >= payable)) &&
                                !isLoadingPricing
                                    ? "bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white shadow-lg shadow-primary-500/30"
                                    : "bg-slate-200 dark:bg-slate-800 text-slate-400 cursor-not-allowed"
                            }`}
                        >
                            {isSubmitting || isLoadingPricing ? (
                                <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                            ) : (
                                <>
                                    <IconReceipt size={18} />
                                    <span>
                                        {!carts.length
                                            ? "Keranjang Kosong"
                                            : !selectedCustomer
                                              ? "Pilih Pelanggan"
                                              : paymentMethod === "cash" &&
                                                  (isDualPayment ? dualCash + dualQris < payable : cash < payable)
                                                ? `Kurang ${formatPrice(
                                                      isDualPayment ? payable - (dualCash + dualQris) : payable - cash,
                                                  )}`
                                                : isLoadingPricing
                                                  ? "Menghitung Promo..."
                                                  : "Selesaikan Transaksi"}
                                    </span>
                                </>
                            )}
                        </button>
                    </div>
                </div>
            </div>

            {/* ===== MODAL QRIS ===== */}
            {showQrisModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div
                        className="absolute inset-0 bg-slate-900/70 backdrop-blur-sm"
                        onClick={() => !isSubmitting && setShowQrisModal(false)}
                    />
                    <div className="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
                        {/* Header */}
                        <div className="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                            <div className="flex items-center gap-2">
                                <IconQrcode size={20} className="text-blue-500" />
                                <h3 className="font-bold text-slate-800 dark:text-white text-base">
                                    {isDualPayment
                                        ? dualStep === 1 ? "Step 1: Terima Tunai" : "Step 2: Scan QRIS"
                                        : paymentMethod === "qris_static"
                                        ? "Scan QRIS untuk Membayar"
                                        : paymentMethod === "doku"
                                        ? "Pembayaran DOKU QRIS"
                                        : paymentMethod === "flip"
                                        ? "Pembayaran Flip.id"
                                        : paymentMethod === "midtrans"
                                        ? "Pembayaran Midtrans"
                                        : "Pembayaran Xendit"}
                                </h3>
                            </div>
                            {!isSubmitting && (
                                <button
                                    onClick={() => { setShowQrisModal(false); setDualStep(1); }}
                                    className="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400"
                                >
                                    <IconX size={18} />
                                </button>
                            )}
                        </div>

                        {/* Step indicator - dual payment only */}
                        {isDualPayment && (
                            <div className="flex items-center gap-0 px-5 pt-4">
                                <div className={`flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold ${dualStep >= 1 ? "bg-primary-500 text-white" : "bg-slate-200 text-slate-500"}`}>1</div>
                                <div className={`flex-1 h-1 ${dualStep >= 2 ? "bg-primary-500" : "bg-slate-200"}`} />
                                <div className={`flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold ${dualStep >= 2 ? "bg-primary-500 text-white" : "bg-slate-200 text-slate-500"}`}>2</div>
                            </div>
                        )}

                        <div className="p-5 space-y-4">
                            {/* Nominal */}
                            <div className="rounded-xl bg-blue-50 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/40 px-4 py-3 text-center">
                                <p className="text-xs text-blue-500 dark:text-blue-400 mb-0.5">
                                    {isDualPayment && dualStep === 1 ? "Terima tunai dari pelanggan" : "Total yang harus dibayar"}
                                </p>
                                <p className="text-2xl font-bold text-blue-700 dark:text-blue-300">
                                    {isDualPayment && dualStep === 1 ? formatPrice(dualCash) : isDualPayment && dualStep === 2 ? formatPrice(dualQris) : formatPrice(payable)}
                                </p>
                                {isDualPayment && (
                                    <p className="text-xs text-slate-400 mt-1">
                                        {dualStep === 1 ? `Sisa QRIS: ${formatPrice(dualQris)}` : `Sudah diterima tunai: ${formatPrice(dualCash)}`}
                                    </p>
                                )}
                            </div>

                            {/* DUAL PAYMENT STEP 1 — terima tunai */}
                            {isDualPayment && dualStep === 1 && (
                                <div className="rounded-xl bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 p-4 space-y-2">
                                    <p className="text-sm font-semibold text-amber-800 dark:text-amber-300">Instruksi Kasir</p>
                                    <ol className="text-xs text-amber-700 dark:text-amber-400 space-y-1 list-decimal list-inside">
                                        <li>Minta pelanggan menyerahkan uang tunai</li>
                                        <li>Terima <strong>{formatPrice(dualCash)}</strong> dari pelanggan</li>
                                        <li>Klik <strong>"Tunai Diterima"</strong> untuk lanjut ke QRIS</li>
                                    </ol>
                                </div>
                            )}

                            {/* DUAL PAYMENT STEP 2 — scan QRIS, reuse existing QRIS UI */}
                            {isDualPayment && dualStep === 2 && (
                                <>
                                    {qrisStaticConfig?.image_url ? (
                                        <div className="flex flex-col items-center gap-3">
                                            <div className="bg-white p-3 rounded-2xl border-2 border-slate-200 dark:border-slate-700 shadow-sm">
                                                <img
                                                    src={qrisStaticConfig.image_url}
                                                    alt="QRIS Code"
                                                    className="w-48 h-48 object-contain"
                                                />
                                            </div>
                                            {qrisStaticConfig.merchant_name && (
                                                <div className="text-center">
                                                    <p className="text-sm font-semibold text-slate-800 dark:text-slate-200">{qrisStaticConfig.merchant_name}</p>
                                                    {qrisStaticConfig.nmid && (
                                                        <p className="text-xs text-slate-400">NMID: {qrisStaticConfig.nmid}</p>
                                                    )}
                                                </div>
                                            )}
                                            <ol className="text-xs text-slate-500 dark:text-slate-400 space-y-1 list-decimal list-inside self-start w-full">
                                                <li>Buka e-wallet atau m-banking pelanggan</li>
                                                <li>Scan QR code di atas</li>
                                                <li>Masukkan nominal <strong>{formatPrice(dualQris)}</strong></li>
                                                <li>Klik <strong>"Konfirmasi Pembayaran"</strong> setelah selesai</li>
                                            </ol>
                                        </div>
                                    ) : (
                                        <div className="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-900/40 dark:bg-amber-950/20 px-4 py-3 text-center">
                                            <p className="text-sm text-amber-700 dark:text-amber-400">
                                                Gambar QRIS belum diupload.<br />
                                                Silakan upload di <strong>Pengaturan</strong>.
                                            </p>
                                        </div>
                                    )}
                                </>
                            )}

                            {/* QRIS STATIS — tampilkan gambar QR */}
                            {paymentMethod === "qris_static" && (
                                <>
                                    {qrisStaticConfig?.image_url ? (
                                        <div className="flex flex-col items-center gap-3">
                                            <div className="bg-white p-3 rounded-2xl border-2 border-slate-200 dark:border-slate-700 shadow-sm">
                                                <img
                                                    src={qrisStaticConfig.image_url}
                                                    alt="QRIS Code"
                                                    className="w-48 h-48 object-contain"
                                                />
                                            </div>
                                            {qrisStaticConfig.merchant_name && (
                                                <div className="text-center">
                                                    <p className="text-sm font-semibold text-slate-800 dark:text-slate-200">
                                                        {qrisStaticConfig.merchant_name}
                                                    </p>
                                                    {qrisStaticConfig.nmid && (
                                                        <p className="text-xs text-slate-400">
                                                            NMID: {qrisStaticConfig.nmid}
                                                        </p>
                                                    )}
                                                </div>
                                            )}
                                            <ol className="text-xs text-slate-500 dark:text-slate-400 space-y-1 list-decimal list-inside self-start w-full">
                                                <li>Buka e-wallet atau m-banking pelanggan</li>
                                                <li>Scan QR code di atas</li>
                                                <li>Masukkan nominal <strong>{formatPrice(payable)}</strong></li>
                                                <li>Klik <strong>"Konfirmasi Pembayaran"</strong> setelah selesai</li>
                                            </ol>
                                        </div>
                                    ) : (
                                        <div className="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-900/40 dark:bg-amber-950/20 px-4 py-3 text-center">
                                            <p className="text-sm text-amber-700 dark:text-amber-400">
                                                Gambar QRIS belum diupload.<br />
                                                Silakan upload di <strong>Pengaturan → Payment</strong>.
                                            </p>
                                        </div>
                                    )}
                                </>
                            )}

                            {/* GATEWAY DINAMIS — tampilkan payment URL */}
                            {["doku", "flip", "midtrans", "xendit"].includes(paymentMethod) && (
                                <div className="space-y-3">
                                    {qrisPaymentUrl ? (
                                        <>
                                            <div className="rounded-xl border border-emerald-200 bg-emerald-50 dark:border-emerald-900/40 dark:bg-emerald-950/20 px-4 py-3">
                                                <p className="text-xs font-semibold text-emerald-700 dark:text-emerald-400 mb-2">
                                                    ✅ Link pembayaran siap!
                                                </p>
                                                <p className="text-xs text-emerald-600 dark:text-emerald-500 break-all mb-2">
                                                    {qrisPaymentUrl}
                                                </p>
                                                <a
                                                    href={qrisPaymentUrl}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700 dark:text-emerald-400 hover:underline"
                                                >
                                                    <IconExternalLink size={13} />
                                                    Buka Link Pembayaran
                                                </a>
                                            </div>
                                            <button
                                                onClick={() => {
                                                    navigator.clipboard.writeText(qrisPaymentUrl);
                                                    toast.success("Link disalin!");
                                                }}
                                                className="w-full py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800"
                                            >
                                                Salin Link ke Clipboard
                                            </button>
                                            <p className="text-xs text-slate-400 text-center">
                                                Bagikan link ke pelanggan, lalu klik Konfirmasi setelah dibayar.
                                            </p>
                                        </>
                                    ) : (
                                        <div className="rounded-xl border border-blue-100 bg-blue-50 dark:border-blue-900/30 dark:bg-blue-950/20 px-4 py-3 text-center">
                                            <p className="text-sm text-blue-600 dark:text-blue-400">
                                                Klik <strong>"Buat Link Pembayaran"</strong> untuk generate link QRIS otomatis dan bagikan ke pelanggan.
                                            </p>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>

                        {/* Footer Buttons */}
                        <div className="px-5 pb-5 flex gap-2">

                            {/* === DUAL PAYMENT BUTTONS === */}
                            {isDualPayment && dualStep === 1 && (
                                <button
                                    onClick={() => setDualStep(2)}
                                    className="flex-1 h-11 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-semibold text-sm flex items-center justify-center gap-2"
                                >
                                    <IconCheck size={16} />
                                    Tunai Diterima &rarr; Lanjut QRIS
                                </button>
                            )}

                            {isDualPayment && dualStep === 2 && (
                                <>
                                    <button
                                        onClick={() => setDualStep(1)}
                                        disabled={isSubmitting}
                                        className="h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-50"
                                    >
                                        &larr; Kembali
                                    </button>
                                    <button
                                        onClick={doSubmitTransaction}
                                        disabled={isSubmitting}
                                        className="flex-1 h-11 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm flex items-center justify-center gap-2 disabled:opacity-60"
                                    >
                                        {isSubmitting ? (
                                            <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                                        ) : (
                                            <>
                                                <IconCheck size={16} />
                                                Konfirmasi Pembayaran
                                            </>
                                        )}
                                    </button>
                                </>
                            )}

                            {/* === QRIS STATIS & GATEWAY DINAMIS BUTTONS === */}
                            {!isDualPayment && (
                                <>
                                    {/* Tombol Buat Link — khusus gateway dinamis yang belum ada URL */}
                                    {["doku", "flip", "midtrans", "xendit"].includes(paymentMethod) && !qrisPaymentUrl && (
                                        <button
                                            onClick={doSubmitTransaction}
                                            disabled={isSubmitting}
                                            className="flex-1 h-11 rounded-xl bg-blue-500 hover:bg-blue-600 text-white font-semibold text-sm flex items-center justify-center gap-2 disabled:opacity-60"
                                        >
                                            {isSubmitting ? (
                                                <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                                            ) : (
                                                <>
                                                    <IconQrcode size={16} />
                                                    Buat Link Pembayaran
                                                </>
                                            )}
                                        </button>
                                    )}

                                    {/* Tombol Konfirmasi Pembayaran — QRIS statis atau gateway dinamis yg sudah ada URL */}
                                    {(paymentMethod === "qris_static" || qrisPaymentUrl) && (
                                        <button
                                            onClick={doSubmitTransaction}
                                            disabled={isSubmitting || (paymentMethod === "qris_static" && !qrisStaticConfig?.image_url)}
                                            className="flex-1 h-11 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold text-sm flex items-center justify-center gap-2 disabled:opacity-60"
                                        >
                                            {isSubmitting ? (
                                                <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                                            ) : (
                                                <>
                                                    <IconCheck size={16} />
                                                    Konfirmasi Pembayaran
                                                </>
                                            )}
                                        </button>
                                    )}
                                </>
                            )}

                            {/* Tombol Batal */}
                            {!isSubmitting && (
                                <button
                                    onClick={() => { setShowQrisModal(false); setDualStep(1); }}
                                    className="h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800"
                                >
                                    Batal
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            )}
            {/* ===== END MODAL QRIS ===== */}

            {/* Numpad Modal */}
            <NumpadModal
                isOpen={numpadOpen}
                onClose={() => setNumpadOpen(false)}
                onConfirm={handleNumpadConfirm}
                title="Jumlah Bayar"
                initialValue={Number(cashInput) || 0}
                isCurrency={true}
            />

            {/* Keyboard Shortcuts Help */}
            {showShortcuts && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div
                        className="absolute inset-0 bg-slate-900/60"
                        onClick={() => setShowShortcuts(false)}
                    />
                    <div className="relative bg-white dark:bg-slate-900 rounded-2xl shadow-xl p-6 max-w-sm w-full">
                        <h3 className="text-lg font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                            <IconKeyboard size={24} />
                            Keyboard Shortcuts
                        </h3>
                        <div className="space-y-3">
                            {[
                                ["F1", "Buka Numpad"],
                                ["F2", "Selesaikan Transaksi"],
                                ["F3", "Toggle Produk/Keranjang"],
                                ["F4", "Tampilkan Bantuan"],
                                ["Esc", "Tutup Modal"],
                            ].map(([key, desc]) => (
                                <div
                                    key={key}
                                    className="flex items-center justify-between"
                                >
                                    <span className="text-slate-600 dark:text-slate-400">
                                        {desc}
                                    </span>
                                    <kbd className="px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded text-sm font-mono font-bold text-slate-700 dark:text-slate-300">
                                        {key}
                                    </kbd>
                                </div>
                            ))}
                        </div>
                        <button
                            onClick={() => setShowShortcuts(false)}
                            className="mt-6 w-full py-2.5 bg-primary-500 hover:bg-primary-600 text-white rounded-xl font-medium"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            )}
        </>
    );
}

Index.layout = (page) => <POSLayout children={page} />;