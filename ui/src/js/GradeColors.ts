export const old_gradecolors: { [key: string]: { bg: string; text: string; dot: string } } =
  {
    "A1": { "bg": "bg-green-100",  "text": "text-green-900",  "dot": "text-green-500"   },
    "A2": { "bg": "bg-green-100",  "text": "text-green-800",  "dot": "text-green-500"   },
    "A3": { "bg": "bg-green-100",  "text": "text-green-800",  "dot": "text-green-400"   },
    "A4": { "bg": "bg-green-50",   "text": "text-green-700",  "dot": "text-green-400"   },
    "A5": { "bg": "bg-green-50",   "text": "text-green-700",  "dot": "text-green-400"   },
    "B1": { "bg": "bg-teal-100",   "text": "text-teal-900",   "dot": "text-teal-500"    },
    "B2": { "bg": "bg-teal-50",    "text": "text-teal-700",   "dot": "text-teal-400"    },
    "B3": { "bg": "bg-teal-50",    "text": "text-teal-700",   "dot": "text-teal-400"    },
    "C1": { "bg": "bg-sky-100",    "text": "text-sky-900",    "dot": "text-sky-500"     },
    "C2": { "bg": "bg-sky-50",     "text": "text-sky-700",    "dot": "text-sky-400"     },
    "C3": { "bg": "bg-sky-50",     "text": "text-sky-700",    "dot": "text-sky-400"     },
    "D1": { "bg": "bg-indigo-100", "text": "text-indigo-900", "dot": "text-indigo-500"  },
    "D2": { "bg": "bg-indigo-50",  "text": "text-indigo-700", "dot": "text-indigo-400"  },
    "D3": { "bg": "bg-indigo-50",  "text": "text-indigo-700", "dot": "text-indigo-400"  },
    "E1": { "bg": "bg-amber-100",  "text": "text-amber-900",  "dot": "text-amber-500"   },
    "E2": { "bg": "bg-amber-50",   "text": "text-amber-700",  "dot": "text-amber-400"   },
    "E3": { "bg": "bg-amber-50",   "text": "text-amber-700",  "dot": "text-amber-400"   },
    "F1": { "bg": "bg-orange-100", "text": "text-orange-900", "dot": "text-orange-500"  },
    "F2": { "bg": "bg-orange-50",  "text": "text-orange-700", "dot": "text-orange-400"  },
    "F3": { "bg": "bg-orange-50",  "text": "text-orange-700", "dot": "text-orange-400"  },
    "G1": { "bg": "bg-rose-100",   "text": "text-rose-900",   "dot": "text-rose-500"    },
    "G2": { "bg": "bg-rose-50",    "text": "text-rose-700",   "dot": "text-rose-400"    },
    "H":  { "bg": "bg-neutral-100","text": "text-neutral-600","dot": "text-neutral-400" },
    "A0": { "bg": "bg-green-100",  "text": "text-green-900",  "dot": "text-green-500"   },
    "B0": { "bg": "bg-teal-100",   "text": "text-teal-900",   "dot": "text-teal-500"    },
    "C0": { "bg": "bg-sky-50",     "text": "text-sky-700",    "dot": "text-sky-400"     },
    "D0": { "bg": "bg-indigo-100", "text": "text-indigo-900", "dot": "text-indigo-500"  },
    "E0": { "bg": "bg-amber-100",  "text": "text-amber-900",  "dot": "text-amber-500"   },
    "F0": { "bg": "bg-orange-100", "text": "text-orange-900", "dot": "text-orange-500"  },
    "G0": { "bg": "bg-rose-100",   "text": "text-rose-900",   "dot": "text-rose-500"    },
  };

export const gradecolors: { [key: string]: { bg: string; text: string; dot: string } } =
{
  // A Tier: Green (Using brand green tokens)
  "A1": { "bg": "bg-brand-light-green",     "text": "text-brand-dark-green font-bold", "dot": "bg-brand-dark-green" },
  "A2": { "bg": "bg-brand-light-green/80",  "text": "text-brand-dark-green",           "dot": "bg-brand-dark-green/80" },
  "A3": { "bg": "bg-brand-light-green/60",  "text": "text-brand-dark-green",           "dot": "bg-brand-dark-green/80" },
  "A4": { "bg": "bg-brand-light-green/40",  "text": "text-brand-dark-green/90",        "dot": "bg-brand-dark-green/60" },
  "A5": { "bg": "bg-brand-light-green/20",  "text": "text-brand-dark-green/80",        "dot": "bg-brand-dark-green/40" },

  // B Tier: Light Blue (Using brand light blue tokens)
  "B1": { "bg": "bg-brand-light-blue",      "text": "text-brand-dark-blue font-bold",  "dot": "bg-brand-dark-blue" },
  "B2": { "bg": "bg-brand-light-blue/60",   "text": "text-brand-dark-blue",            "dot": "bg-brand-dark-blue/80" },
  "B3": { "bg": "bg-brand-light-blue/30",   "text": "text-brand-dark-blue/80",         "dot": "bg-brand-dark-blue/50" },

    // C Tier: University Blue (Optimized for text contrast and clear stepping)
  "C1": { "bg": "bg-university-blue",       "text": "text-white font-bold",            "dot": "bg-white" },
  "C2": { "bg": "bg-university-blue/50",    "text": "text-brand-dark-blue font-bold",  "dot": "bg-brand-dark-blue" },
  "C3": { "bg": "bg-university-blue/20",    "text": "text-brand-dark-blue",            "dot": "bg-brand-dark-blue/70" },

  // D Tier: Light Purple (Using brand light purple tokens)
  "D1": { "bg": "bg-brand-light-purple",    "text": "text-brand-dark-purple font-bold", "dot": "bg-brand-dark-purple" },
  "D2": { "bg": "bg-brand-light-purple/60", "text": "text-brand-dark-purple",           "dot": "bg-brand-dark-purple/80" },
  "D3": { "bg": "bg-brand-light-purple/30", "text": "text-brand-dark-purple/80",        "dot": "bg-brand-dark-purple/50" },

  // E Tier: Yellow (Using brand light yellow tokens)
  "E1": { "bg": "bg-brand-light-yellow",    "text": "text-brand-dark-purple font-bold", "dot": "bg-brand-dark-purple/80" },
  "E2": { "bg": "bg-brand-light-yellow/60", "text": "text-brand-dark-purple",           "dot": "bg-brand-dark-purple/60" },
  "E3": { "bg": "bg-brand-light-yellow/30", "text": "text-brand-dark-purple/80",        "dot": "bg-brand-dark-purple/40" },

  // F Tier: Pink (Using brand pink tokens instead of generic orange)
  "F1": { "bg": "bg-brand-light-pink",      "text": "text-brand-dark-pink font-bold",  "dot": "bg-brand-dark-pink" },
  "F2": { "bg": "bg-brand-light-pink/60",   "text": "text-brand-dark-pink",            "dot": "bg-brand-dark-pink/80" },
  "F3": { "bg": "bg-brand-light-pink/30",   "text": "text-brand-dark-pink/80",         "dot": "bg-brand-dark-pink/50" },

  // G Tier: Red Low Grades (Using light variations of brand dark red)
  "G1": { "bg": "bg-brand-dark-red/10",     "text": "text-brand-dark-red",             "dot": "bg-brand-dark-red/60" },
  "G2": { "bg": "bg-brand-dark-red/30",     "text": "text-brand-dark-red font-semibold", "dot": "bg-brand-dark-red/80" },
  
  // H Tier: Critical Red (Solid brand dark red with stark contrast)
  "H":  { "bg": "bg-brand-dark-red",        "text": "text-white font-black",           "dot": "bg-white" },

  // 0-Baselines (Aligned with their tier roots)
  "A0": { "bg": "bg-brand-light-green",     "text": "text-brand-dark-green font-bold", "dot": "bg-brand-dark-green" },
  "B0": { "bg": "bg-brand-light-blue",      "text": "text-brand-dark-blue font-bold",  "dot": "bg-brand-dark-blue" },
  "C0": { "bg": "bg-university-blue",       "text": "text-white font-bold",            "dot": "bg-white" },
  "D0": { "bg": "bg-brand-light-purple",    "text": "text-brand-dark-purple font-bold", "dot": "bg-brand-dark-purple" },
  "E0": { "bg": "bg-brand-light-yellow",    "text": "text-brand-dark-purple font-bold", "dot": "bg-brand-dark-purple/80" },
  "F0": { "bg": "bg-brand-light-pink",      "text": "text-brand-dark-pink font-bold",  "dot": "bg-brand-dark-pink" },
  "G0": { "bg": "bg-brand-dark-red/40",     "text": "text-brand-dark-red font-bold",   "dot": "bg-brand-dark-red" }
}


