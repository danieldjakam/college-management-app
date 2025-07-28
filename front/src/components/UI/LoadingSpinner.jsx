import React from 'react';

const LoadingSpinner = ({
  size = 'default',
  color = 'primary',
  overlay = false,
  text = null,
  className = ''
}) => {
  const sizeClasses = {
    sm: 'w-4 h-4 border-2',
    default: 'w-10 h-10 border-4',
    lg: 'w-16 h-16 border-4',
    xl: 'w-20 h-20 border-4'
  };

  const colorClasses = {
    primary: 'border-primary-violet',
    gray: 'border-gray-300',
    white: 'border-white'
  };

  const spinnerClasses = [
    'loading-spinner',
    sizeClasses[size],
    colorClasses[color],
    className
  ].filter(Boolean).join(' ');

  const spinner = (
    <div className="flex flex-col items-center gap-3">
      <div className={spinnerClasses}></div>
      {text && (
        <span className="text-sm text-gray-600 font-medium">{text}</span>
      )}
    </div>
  );

  if (overlay) {
    return (
      <div className="loading-overlay">
        {spinner}
      </div>
    );
  }

  return spinner;
};

export default LoadingSpinner;