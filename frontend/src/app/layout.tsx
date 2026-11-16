import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import "./globals.css";
import { AppShell } from "@/components/layout/AppShell";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "Coin Hunters — Get Paid for Testing Apps, Games & Surveys",
  description: "Join Coin Hunters to earn crypto by completing offerwalls, mobile games, and high-paying surveys. Instant crypto withdrawals via LTC, USDT, BTC, and SOL.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html
      lang="en"
      className={`${geistSans.variable} ${geistMono.variable} h-full dark antialiased`}
    >
      <body className="min-h-full flex flex-col bg-[#120E0E] text-gray-100 selection:bg-[#FF5A36] selection:text-white">
        <AppShell>{children}</AppShell>
      </body>
    </html>
  );
}
